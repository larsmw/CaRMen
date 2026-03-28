import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useParams, Link, useNavigate } from 'react-router-dom'
import client from '../api/client'
import type { Activity } from '../types'
import Modal from '../components/Modal'
import ActivityForm from '../components/forms/ActivityForm'
import styles from './ActivityDetail.module.scss'
import { usePageTitle } from '../hooks/usePageTitle'

const TYPE_ICON: Record<string, string> = {
  call: '📞', email: '✉️', meeting: '📅', task: '✓', note: '📝',
}

const statusClass: Record<string, string> = {
  planned:   'statusPlanned',
  completed: 'statusCompleted',
  cancelled: 'statusCancelled',
}

export default function ActivityDetail() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [showEdit, setShowEdit] = useState(false)

  const { data: activity, isLoading } = useQuery({
    queryKey: ['activity', id],
    queryFn: () => client.get<Activity>(`/api/activities/${id}`).then(r => r.data),
  })
  usePageTitle(activity?.subject ?? 'Activity')

  const deleteMutation = useMutation({
    mutationFn: () => client.delete(`/api/activities/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['activities'] }); navigate('/activities') },
  })

  if (isLoading) return <p>Loading...</p>
  if (!activity) return <p>Activity not found.</p>

  return (
    <div>
      <div>
        <Link to="/activities" className={styles.backLink}>&larr; Activities</Link>
      </div>

      <div className={styles.pageHeader}>
        <div>
          <p className={styles.typeLabel}>
            {TYPE_ICON[activity.type]} {activity.type.charAt(0).toUpperCase() + activity.type.slice(1)}
          </p>
          <h1 className={styles.pageTitle}>{activity.subject}</h1>
        </div>
        <div className={styles.actions}>
          <span className={`${styles.badge} ${styles[statusClass[activity.status]] ?? ''}`}>{activity.status}</span>
          <button className={styles.editBtn} onClick={() => setShowEdit(true)}>Edit</button>
          <button className={styles.deleteBtn} onClick={() => { if (window.confirm(`Delete "${activity.subject}"?`)) deleteMutation.mutate() }}>Delete</button>
        </div>
      </div>

      <div className={styles.grid2}>
        <div className={styles.card}>
          <h2 className={styles.sectionTitle}>Details</h2>
          <Field label="Contact" value={activity.contact ? (
            <Link to={`/contacts/${activity.contact.id}`} style={{ color: '#3b82f6' }}>{activity.contact.fullName}</Link>
          ) : null} />
          <Field label="Deal" value={activity.deal ? (
            <Link to={`/deals/${activity.deal.id}`} style={{ color: '#3b82f6' }}>{activity.deal.title}</Link>
          ) : null} />
          <Field label="Assigned To" value={activity.assignedTo?.fullName} />
          <Field label="Scheduled" value={activity.scheduledAt ? new Date(activity.scheduledAt).toLocaleString() : null} />
          <Field label="Completed" value={activity.completedAt ? new Date(activity.completedAt).toLocaleString() : null} />
        </div>
        <div className={styles.card}>
          <h2 className={styles.sectionTitle}>Description</h2>
          <p className={activity.description ? styles.description : styles.descriptionEmpty}>
            {activity.description ?? 'No description.'}
          </p>
        </div>
      </div>

      {showEdit && (
        <Modal title="Edit Activity" onClose={() => setShowEdit(false)}>
          <ActivityForm initial={activity} onDone={() => setShowEdit(false)} />
        </Modal>
      )}
    </div>
  )
}

function Field({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className={styles.fieldRow}>
      <span className={styles.fieldLabel}>{label}</span>
      <span className={styles.fieldValue}>{value ?? <span className={styles.fieldEmpty}>—</span>}</span>
    </div>
  )
}
