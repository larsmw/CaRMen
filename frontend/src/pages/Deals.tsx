import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import client from '../api/client'
import type { Deal, PaginatedResponse } from '../types'
import Modal from '../components/Modal'
import DealForm from '../components/forms/DealForm'
import Pagination from '../components/Pagination'
import styles from './Deals.module.scss'

const PER_PAGE = 20

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

export default function Deals() {
  const [page, setPage] = useState(1)
  const [showCreate, setShowCreate] = useState(false)
  const qc = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['deals', page],
    queryFn: () =>
      client.get<PaginatedResponse<Deal>>('/api/deals', {
        params: { page, itemsPerPage: PER_PAGE },
        headers: { Accept: 'application/ld+json' },
      }).then(r => r.data),
  })

  const deleteMutation = useMutation({
    mutationFn: (id: string) => client.delete(`/api/deals/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['deals'] }),
  })

  const confirmDelete = (id: string, title: string) => {
    if (window.confirm(`Delete "${title}"?`)) deleteMutation.mutate(id)
  }

  const deals = data?.['hydra:member'] ?? []
  const total = data?.['hydra:totalItems'] ?? 0

  return (
    <div>
      <div className={styles.header}>
        <h1 className={styles.title}>Deals</h1>
        <button onClick={() => setShowCreate(true)} className={styles.newBtn}>+ New Deal</button>
      </div>

      {isLoading ? <p>Loading...</p> : (
        <>
          <div className={styles.tableWrap}>
            <table className={styles.table}>
              <thead>
                <tr className={styles.theadRow}>
                  <th className={styles.th}>Title</th>
                  <th className={styles.th}>Customer</th>
                  <th className={styles.th}>Stage</th>
                  <th className={styles.th}>Value</th>
                  <th className={styles.th}>Close Date</th>
                  <th className={styles.th}>Owner</th>
                  <th className={styles.th}></th>
                </tr>
              </thead>
              <tbody>
                {deals.map(d => (
                  <tr key={d.id} className={styles.tbodyRow}>
                    <td className={styles.td}>
                      <Link to={`/deals/${d.id}`} className={styles.link}>{d.title}</Link>
                    </td>
                    <td className={styles.td}>{d.account?.name ?? d.primaryContact?.fullName ?? '-'}</td>
                    <td className={styles.td}>
                      <span className={`${styles.badge} ${styles[stageClass[d.stage]] ?? ''}`}>{STAGE_LABELS[d.stage]}</span>
                    </td>
                    <td className={styles.td}>${Number(d.value).toLocaleString()} {d.currency}</td>
                    <td className={styles.td}>{d.closeDate ? new Date(d.closeDate).toLocaleDateString() : '-'}</td>
                    <td className={styles.td}>{d.owner?.fullName ?? '-'}</td>
                    <td className={styles.tdRight}>
                      <button onClick={() => confirmDelete(d.id, d.title)} className={styles.delBtn}>Delete</button>
                    </td>
                  </tr>
                ))}
                {deals.length === 0 && (
                  <tr><td colSpan={7} className={styles.emptyCell}>No deals found</td></tr>
                )}
              </tbody>
            </table>
          </div>
          <Pagination page={page} totalItems={total} perPage={PER_PAGE} onChange={setPage} />
        </>
      )}

      {showCreate && (
        <Modal title="New Deal" onClose={() => setShowCreate(false)}>
          <DealForm onDone={() => setShowCreate(false)} />
        </Modal>
      )}
    </div>
  )
}
