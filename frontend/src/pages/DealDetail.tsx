import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useParams, Link, useNavigate } from 'react-router-dom'
import client from '../api/client'
import type { Deal, Activity, PaginatedResponse } from '../types'
import Modal from '../components/Modal'
import DealForm from '../components/forms/DealForm'
import styles from './DealDetail.module.scss'
import { usePageTitle } from '../hooks/usePageTitle'

const STAGE_LABELS: Record<string, string> = {
  prospecting: 'Prospecting', qualification: 'Qualification', proposal: 'Proposal',
  negotiation: 'Negotiation', closed_won: 'Closed Won', closed_lost: 'Closed Lost',
}

const stageClass: Record<string, string> = {
  prospecting:   'stageProspecting',
  qualification: 'stageQualification',
  proposal:      'stageProposal',
  negotiation:   'stageNegotiation',
  closed_won:    'stageClosedWon',
  closed_lost:   'stageClosedLost',
}

export default function DealDetail() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [showEdit, setShowEdit] = useState(false)

  const { data: deal, isLoading } = useQuery({
    queryKey: ['deal', id],
    queryFn: () => client.get<Deal>(`/api/deals/${id}`).then(r => r.data),
  })
  usePageTitle(deal?.title ?? 'Deal')

  const { data: activitiesData } = useQuery({
    queryKey: ['deal-activities', id],
    queryFn: () => client.get<PaginatedResponse<Activity>>('/api/activities', {
      params: { 'deal.id': id }, headers: { Accept: 'application/ld+json' },
    }).then(r => r.data),
    enabled: !!id,
  })

  const deleteMutation = useMutation({
    mutationFn: () => client.delete(`/api/deals/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['deals'] }); navigate('/deals') },
  })

  if (isLoading) return <p>Loading...</p>
  if (!deal) return <p>Deal not found.</p>

  const activities = activitiesData?.['hydra:member'] ?? []

  return (
    <div>
      <div>
        <Link to="/deals" className={styles.backLink}>&larr; Deals</Link>
      </div>

      <div className={styles.pageHeader}>
        <div>
          <h1 className={styles.pageTitle}>{deal.title}</h1>
          <p className={styles.dealValue}>
            ${Number(deal.value).toLocaleString()} {deal.currency}
          </p>
        </div>
        <div className={styles.actions}>
          <span className={`${styles.badge} ${styles[stageClass[deal.stage]] ?? ''}`}>{STAGE_LABELS[deal.stage]}</span>
          <button className={styles.editBtn} onClick={() => setShowEdit(true)}>Edit</button>
          <button className={styles.deleteBtn} onClick={() => { if (window.confirm(`Delete "${deal.title}"?`)) deleteMutation.mutate() }}>Delete</button>
        </div>
      </div>

      <div className={styles.grid2}>
        <div className={styles.card}>
          <h2 className={styles.sectionTitle}>Details</h2>
          <Field label="Customer" value={
            deal.account
              ? <Link to={`/accounts/${deal.account.id}`} style={{ color: '#3b82f6' }}>{deal.account.name}</Link>
              : deal.primaryContact
                ? <Link to={`/contacts/${deal.primaryContact.id}`} style={{ color: '#3b82f6' }}>{deal.primaryContact.fullName}</Link>
                : null
          } />
          {deal.account && deal.primaryContact && (
            <Field label="Contact" value={
              <Link to={`/contacts/${deal.primaryContact.id}`} style={{ color: '#3b82f6' }}>{deal.primaryContact.fullName}</Link>
            } />
          )}
          <Field label="Owner" value={deal.owner?.fullName} />
          <Field label="Probability" value={`${deal.probability}%`} />
          <Field label="Close Date" value={deal.closeDate ? new Date(deal.closeDate).toLocaleDateString() : null} />
        </div>
        <div className={styles.card}>
          <h2 className={styles.sectionTitle}>Description</h2>
          <p className={deal.description ? styles.description : styles.descriptionEmpty}>
            {deal.description ?? 'No description.'}
          </p>
          {deal.lostReason && (
            <>
              <h2 className={`${styles.sectionTitle} ${styles.sectionTitleMt}`}>Lost Reason</h2>
              <p className={styles.lostReason}>{deal.lostReason}</p>
            </>
          )}
        </div>
      </div>

      <div className={styles.card}>
        <h2 className={styles.sectionTitle}>Activities ({activities.length})</h2>
        {activities.length === 0 ? <p className={styles.empty}>No activities.</p> : (
          <table className={styles.activitiesTable}>
            <thead>
              <tr>
                <th className={styles.th}>Type</th>
                <th className={styles.th}>Subject</th>
                <th className={styles.th}>Assigned To</th>
                <th className={styles.th}>Scheduled</th>
                <th className={styles.th}>Status</th>
              </tr>
            </thead>
            <tbody>
              {activities.map(a => (
                <tr key={a.id} className={styles.activityRow}>
                  <td className={styles.td}>{a.type}</td>
                  <td className={styles.td}><Link to={`/activities/${a.id}`} style={{ color: '#3b82f6' }}>{a.subject}</Link></td>
                  <td className={styles.td}>{a.assignedTo?.fullName ?? '-'}</td>
                  <td className={styles.td}>{a.scheduledAt ? new Date(a.scheduledAt).toLocaleDateString() : '-'}</td>
                  <td className={styles.td}>{a.status}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {showEdit && (
        <Modal title="Edit Deal" onClose={() => setShowEdit(false)}>
          <DealForm initial={deal} onDone={() => setShowEdit(false)} />
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
