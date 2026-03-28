import { useQuery } from '@tanstack/react-query'
import client from '../api/client'
import type { DashboardStats } from '../types'
import styles from './Dashboard.module.scss'
import { usePageTitle } from '../hooks/usePageTitle'

const STAGE_LABELS: Record<string, string> = {
  prospecting: 'Prospecting',
  qualification: 'Qualification',
  proposal: 'Proposal',
  negotiation: 'Negotiation',
  closed_won: 'Closed Won',
  closed_lost: 'Closed Lost',
}

export default function Dashboard() {
  usePageTitle('Dashboard')
  const { data, isLoading } = useQuery({
    queryKey: ['dashboard'],
    queryFn: () => client.get<DashboardStats>('/api/dashboard/stats').then(r => r.data),
  })

  if (isLoading) return <p>Loading...</p>

  const stats = [
    { label: 'Contacts',      value: data?.contacts      ?? 0 },
    { label: 'Accounts',      value: data?.accounts      ?? 0 },
    { label: 'Open Deals',    value: data?.open_deals    ?? 0 },
    { label: 'Pending Tasks', value: data?.pending_tasks ?? 0 },
  ]

  return (
    <div>
      <h1 className={styles.title}>Dashboard</h1>

      <div className={styles.statsGrid}>
        {stats.map(s => (
          <div key={s.label} className={styles.card}>
            <div className={styles.cardValue}>{s.value}</div>
            <div className={styles.cardLabel}>{s.label}</div>
          </div>
        ))}
      </div>

      <h2 className={styles.sectionTitle}>Pipeline</h2>
      <div className={styles.tableWrap}>
        <table className={styles.table}>
          <thead>
            <tr className={styles.theadRow}>
              <th className={styles.th}>Stage</th>
              <th className={styles.th}>Deals</th>
              <th className={styles.th}>Total Value</th>
            </tr>
          </thead>
          <tbody>
            {data?.pipeline.map(row => (
              <tr key={row.stage} className={styles.tbodyRow}>
                <td className={styles.td}>{STAGE_LABELS[row.stage] ?? row.stage}</td>
                <td className={styles.td}>{row.count}</td>
                <td className={styles.td}>${Number(row.total_value).toLocaleString()}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
