import { Link } from 'react-router-dom'
import type { Activity } from '../types'
import styles from './ActivityCalendar.module.scss'

export type CalendarMode = 'week' | 'month'

const TYPE_ICON: Record<string, string> = {
  call: '📞', email: '✉️', meeting: '📅', task: '✓', note: '📝',
}

const TYPE_CLASS: Record<string, string> = {
  call: 'typeCall', email: 'typeEmail', meeting: 'typeMeeting',
  task: 'typeTask', note: 'typeNote',
}

const WEEK_DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

function weekStart(date: Date): Date {
  const d = new Date(date)
  d.setHours(0, 0, 0, 0)
  const day = d.getDay()
  d.setDate(d.getDate() - (day === 0 ? 6 : day - 1))
  return d
}

function addDays(d: Date, n: number): Date {
  const r = new Date(d)
  r.setDate(r.getDate() + n)
  return r
}

function sameDay(a: Date, b: Date): boolean {
  return a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
}

function activityDay(a: Activity): Date | null {
  if (!a.scheduledAt) return null
  return new Date(a.scheduledAt)
}

function sortByTime(activities: Activity[]): Activity[] {
  return [...activities].sort((a, b) => (a.scheduledAt ?? '').localeCompare(b.scheduledAt ?? ''))
}

function ActivityChip({ activity }: { activity: Activity }) {
  const cls = TYPE_CLASS[activity.type] ?? 'typeNote'
  const time = activity.scheduledAt
    ? new Date(activity.scheduledAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    : null
  return (
    <Link
      to={`/activities/${activity.id}`}
      className={[
        styles.chip,
        styles[cls],
        activity.status === 'completed' ? styles.completed : '',
        activity.status === 'cancelled' ? styles.cancelled : '',
      ].join(' ')}
      title={`${activity.type}: ${activity.subject}${time ? ' @ ' + time : ''}`}
    >
      <span className={styles.chipIcon}>{TYPE_ICON[activity.type]}</span>
      <span className={styles.chipSubject}>{activity.subject}</span>
      {time && <span className={styles.chipTime}>{time}</span>}
    </Link>
  )
}

function WeekView({ currentDate, activities }: { currentDate: Date; activities: Activity[] }) {
  const monday = weekStart(currentDate)
  const days = Array.from({ length: 7 }, (_, i) => addDays(monday, i))
  const today = new Date()

  return (
    <div className={styles.weekGrid}>
      {days.map((day, i) => {
        const isToday = sameDay(day, today)
        const dayActivities = sortByTime(
          activities.filter(a => { const d = activityDay(a); return d && sameDay(d, day) })
        )
        return (
          <div key={i} className={`${styles.weekCol} ${isToday ? styles.weekToday : ''}`}>
            <div className={styles.weekColHeader}>
              <span className={styles.weekDayName}>{WEEK_DAYS[i]}</span>
              <span className={`${styles.weekDayNum} ${isToday ? styles.todayCircle : ''}`}>
                {day.getDate()}
              </span>
            </div>
            <div className={styles.weekColBody}>
              {dayActivities.map(a => <ActivityChip key={a.id} activity={a} />)}
            </div>
          </div>
        )
      })}
    </div>
  )
}

function MonthView({ currentDate, activities }: { currentDate: Date; activities: Activity[] }) {
  const today = new Date()
  const year = currentDate.getFullYear()
  const month = currentDate.getMonth()

  const firstDay = new Date(year, month, 1)
  const gridStart = weekStart(firstDay)
  const cells = Array.from({ length: 42 }, (_, i) => addDays(gridStart, i))
  const rows: Date[][] = []
  for (let i = 0; i < 6; i++) rows.push(cells.slice(i * 7, i * 7 + 7))
  const lastRow = rows[5]
  const displayRows = lastRow.some(d => d.getMonth() === month) ? rows : rows.slice(0, 5)

  return (
    <div className={styles.monthGrid}>
      <div className={styles.monthDayNames}>
        {WEEK_DAYS.map(d => <div key={d} className={styles.monthDayName}>{d}</div>)}
      </div>
      <div className={styles.monthBody}>
        {displayRows.map((row, ri) => (
          <div key={ri} className={styles.monthRow}>
            {row.map((day, ci) => {
              const isCurrentMonth = day.getMonth() === month
              const isToday = sameDay(day, today)
              const dayActivities = sortByTime(
                activities.filter(a => { const d = activityDay(a); return d && sameDay(d, day) })
              )
              return (
                <div
                  key={ci}
                  className={[
                    styles.monthCell,
                    !isCurrentMonth ? styles.otherMonth : '',
                    isToday ? styles.todayCell : '',
                  ].join(' ')}
                >
                  <span className={`${styles.monthDayNum} ${isToday ? styles.todayCircle : ''}`}>
                    {day.getDate()}
                  </span>
                  <div className={styles.monthCellActivities}>
                    {dayActivities.slice(0, 3).map(a => <ActivityChip key={a.id} activity={a} />)}
                    {dayActivities.length > 3 && (
                      <span className={styles.moreCount}>+{dayActivities.length - 3} more</span>
                    )}
                  </div>
                </div>
              )
            })}
          </div>
        ))}
      </div>
    </div>
  )
}

interface Props {
  mode: CalendarMode
  currentDate: Date
  activities: Activity[]
}

export default function ActivityCalendar({ mode, currentDate, activities }: Props) {
  if (mode === 'week') return <WeekView currentDate={currentDate} activities={activities} />
  return <MonthView currentDate={currentDate} activities={activities} />
}

export function getCalendarRange(mode: CalendarMode, date: Date): { start: Date; end: Date } {
  if (mode === 'week') {
    const start = weekStart(date)
    const end = addDays(start, 6)
    end.setHours(23, 59, 59, 999)
    return { start, end }
  }
  const year = date.getFullYear()
  const month = date.getMonth()
  const start = weekStart(new Date(year, month, 1))
  const lastDay = new Date(year, month + 1, 0)
  const end = addDays(weekStart(lastDay), 6)
  end.setHours(23, 59, 59, 999)
  return { start, end }
}

export function formatRangeLabel(mode: CalendarMode, date: Date): string {
  if (mode === 'week') {
    const start = weekStart(date)
    const end = addDays(start, 6)
    if (start.getMonth() === end.getMonth()) {
      return `${start.toLocaleDateString('en-US', { month: 'long' })} ${start.getDate()}–${end.getDate()}, ${start.getFullYear()}`
    }
    return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} – ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`
  }
  return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
}
