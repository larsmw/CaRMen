import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import client from '../api/client'
import type { Activity, PaginatedResponse } from '../types'
import { usePageTitle } from '../hooks/usePageTitle'
import Modal from '../components/Modal'
import ActivityForm from '../components/forms/ActivityForm'
import Pagination from '../components/Pagination'
import ActivityCalendar, {
  type CalendarMode,
  getCalendarRange,
  formatRangeLabel,
} from '../components/ActivityCalendar'
import { useLocale } from '../context/LocaleContext'
import styles from './Activities.module.scss'

type ViewMode = 'calendar' | 'list'

const PER_PAGE = 20

const TYPE_ICON: Record<string, string> = {
  call: '📞', email: '✉️', meeting: '📅', task: '✓', note: '📝',
}

const statusClass: Record<string, string> = {
  planned:   'statusPlanned',
  completed: 'statusCompleted',
  cancelled: 'statusCancelled',
}

export default function Activities() {
  usePageTitle('Activities')
  const { locale } = useLocale()
  const [viewMode, setViewMode] = useState<ViewMode>('calendar')
  const [calMode, setCalMode] = useState<CalendarMode>('month')
  const [currentDate, setCurrentDate] = useState(() => new Date())
  const [page, setPage] = useState(1)
  const [showCreate, setShowCreate] = useState(false)
  const qc = useQueryClient()

  const { start, end } = getCalendarRange(calMode, currentDate)

  const { data: calData, isLoading: calLoading } = useQuery({
    queryKey: ['activities-cal', calMode, start.toISOString()],
    queryFn: () =>
      client.get<PaginatedResponse<Activity>>('/api/activities', {
        params: {
          'scheduledAt[after]': start.toISOString(),
          'scheduledAt[before]': end.toISOString(),
          itemsPerPage: 200,
        },
        headers: { Accept: 'application/ld+json' },
      }).then(r => r.data),
    enabled: viewMode === 'calendar',
  })

  const { data: listData, isLoading: listLoading } = useQuery({
    queryKey: ['activities', page],
    queryFn: () =>
      client.get<PaginatedResponse<Activity>>('/api/activities', {
        params: { page, itemsPerPage: PER_PAGE },
        headers: { Accept: 'application/ld+json' },
      }).then(r => r.data),
    enabled: viewMode === 'list',
  })

  const deleteMutation = useMutation({
    mutationFn: (id: string) => client.delete(`/api/activities/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['activities'] }),
  })

  const confirmDelete = (id: string, subject: string) => {
    if (window.confirm(`Delete "${subject}"?`)) deleteMutation.mutate(id)
  }

  function navigate(dir: 1 | -1) {
    const d = new Date(currentDate)
    if (calMode === 'week') d.setDate(d.getDate() + dir * 7)
    else d.setMonth(d.getMonth() + dir)
    setCurrentDate(d)
  }

  const calActivities = calData?.['member'] ?? []
  const listActivities = listData?.['member'] ?? []
  const total = listData?.['totalItems'] ?? 0

  return (
    <div>
      <div className={styles.header}>
        <h1 className={styles.title}>Activities</h1>
        <div className={styles.headerControls}>
          <div className={styles.viewToggle}>
            <button
              className={`${styles.toggleBtn} ${viewMode === 'calendar' ? styles.toggleActive : ''}`}
              onClick={() => setViewMode('calendar')}
            >Calendar</button>
            <button
              className={`${styles.toggleBtn} ${viewMode === 'list' ? styles.toggleActive : ''}`}
              onClick={() => setViewMode('list')}
            >List</button>
          </div>
          <button onClick={() => setShowCreate(true)} className={styles.newBtn}>+ New Activity</button>
        </div>
      </div>

      {viewMode === 'calendar' && (
        <>
          <div className={styles.calBar}>
            <div className={styles.calBarLeft}>
              <button className={styles.navBtn} onClick={() => navigate(-1)}>‹</button>
              <button className={styles.todayBtn} onClick={() => setCurrentDate(new Date())}>Today</button>
              <button className={styles.navBtn} onClick={() => navigate(1)}>›</button>
              <span className={styles.calTitle}>{formatRangeLabel(calMode, currentDate, locale)}</span>
            </div>
            <div className={styles.viewToggle}>
              <button
                className={`${styles.toggleBtn} ${calMode === 'month' ? styles.toggleActive : ''}`}
                onClick={() => setCalMode('month')}
              >Month</button>
              <button
                className={`${styles.toggleBtn} ${calMode === 'week' ? styles.toggleActive : ''}`}
                onClick={() => setCalMode('week')}
              >Week</button>
            </div>
          </div>
          {calLoading
            ? <p>Loading...</p>
            : <ActivityCalendar mode={calMode} currentDate={currentDate} activities={calActivities} />
          }
        </>
      )}

      {viewMode === 'list' && (
        listLoading ? <p>Loading...</p> : (
          <>
            <div className={styles.tableWrap}>
              <table className={styles.table}>
                <thead>
                  <tr className={styles.theadRow}>
                    <th className={styles.th}>Type</th>
                    <th className={styles.th}>Subject</th>
                    <th className={styles.th}>Contact</th>
                    <th className={styles.th}>Deal</th>
                    <th className={styles.th}>Scheduled</th>
                    <th className={styles.th}>Status</th>
                    <th className={styles.th}></th>
                  </tr>
                </thead>
                <tbody>
                  {listActivities.map(a => (
                    <tr key={a.id} className={styles.tbodyRow}>
                      <td className={styles.td}>{TYPE_ICON[a.type]} {a.type}</td>
                      <td className={styles.td}>
                        <Link to={`/activities/${a.id}`} className={styles.link}>{a.subject}</Link>
                      </td>
                      <td className={styles.td}>{a.contact?.fullName ?? '-'}</td>
                      <td className={styles.td}>{a.deal?.title ?? '-'}</td>
                      <td className={styles.td}>{a.scheduledAt ? new Date(a.scheduledAt).toLocaleString(locale) : '-'}</td>
                      <td className={styles.td}>
                        <span className={`${styles.badge} ${styles[statusClass[a.status]] ?? ''}`}>{a.status}</span>
                      </td>
                      <td className={styles.tdRight}>
                        <button onClick={() => confirmDelete(a.id, a.subject)} className={styles.delBtn}>Delete</button>
                      </td>
                    </tr>
                  ))}
                  {listActivities.length === 0 && (
                    <tr><td colSpan={7} className={styles.emptyCell}>No activities found</td></tr>
                  )}
                </tbody>
              </table>
            </div>
            <Pagination page={page} totalItems={total} perPage={PER_PAGE} onChange={setPage} />
          </>
        )
      )}

      {showCreate && (
        <Modal title="New Activity" onClose={() => setShowCreate(false)}>
          <ActivityForm onDone={() => {
            setShowCreate(false)
            qc.invalidateQueries({ queryKey: ['activities-cal'] })
            qc.invalidateQueries({ queryKey: ['activities'] })
          }} />
        </Modal>
      )}
    </div>
  )
}
