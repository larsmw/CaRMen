import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import client from '../api/client'
import type { Activity, PaginatedResponse } from '../types'
import Modal from '../components/Modal'
import ActivityForm from '../components/forms/ActivityForm'
import Pagination from '../components/Pagination'
import styles from './Activities.module.scss'

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
  const [page, setPage] = useState(1)
  const [showCreate, setShowCreate] = useState(false)
  const qc = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['activities', page],
    queryFn: () =>
      client.get<PaginatedResponse<Activity>>('/api/activities', {
        params: { page, itemsPerPage: PER_PAGE },
        headers: { Accept: 'application/ld+json' },
      }).then(r => r.data),
  })

  const deleteMutation = useMutation({
    mutationFn: (id: string) => client.delete(`/api/activities/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['activities'] }),
  })

  const confirmDelete = (id: string, subject: string) => {
    if (window.confirm(`Delete "${subject}"?`)) deleteMutation.mutate(id)
  }

  const activities = data?.['hydra:member'] ?? []
  const total = data?.['hydra:totalItems'] ?? 0

  return (
    <div>
      <div className={styles.header}>
        <h1 className={styles.title}>Activities</h1>
        <button onClick={() => setShowCreate(true)} className={styles.newBtn}>+ New Activity</button>
      </div>

      {isLoading ? <p>Loading...</p> : (
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
                {activities.map(a => (
                  <tr key={a.id} className={styles.tbodyRow}>
                    <td className={styles.td}>{TYPE_ICON[a.type]} {a.type}</td>
                    <td className={styles.td}>
                      <Link to={`/activities/${a.id}`} className={styles.link}>{a.subject}</Link>
                    </td>
                    <td className={styles.td}>{a.contact?.fullName ?? '-'}</td>
                    <td className={styles.td}>{a.deal?.title ?? '-'}</td>
                    <td className={styles.td}>{a.scheduledAt ? new Date(a.scheduledAt).toLocaleString() : '-'}</td>
                    <td className={styles.td}>
                      <span className={`${styles.badge} ${styles[statusClass[a.status]] ?? ''}`}>{a.status}</span>
                    </td>
                    <td className={styles.tdRight}>
                      <button onClick={() => confirmDelete(a.id, a.subject)} className={styles.delBtn}>Delete</button>
                    </td>
                  </tr>
                ))}
                {activities.length === 0 && (
                  <tr><td colSpan={7} className={styles.emptyCell}>No activities found</td></tr>
                )}
              </tbody>
            </table>
          </div>
          <Pagination page={page} totalItems={total} perPage={PER_PAGE} onChange={setPage} />
        </>
      )}

      {showCreate && (
        <Modal title="New Activity" onClose={() => setShowCreate(false)}>
          <ActivityForm onDone={() => setShowCreate(false)} />
        </Modal>
      )}
    </div>
  )
}
