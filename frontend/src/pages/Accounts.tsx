import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import client from '../api/client'
import type { Account, PaginatedResponse } from '../types'
import Modal from '../components/Modal'
import AccountForm from '../components/forms/AccountForm'
import Pagination from '../components/Pagination'
import styles from './Accounts.module.scss'

const PER_PAGE = 20

export default function Accounts() {
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [showCreate, setShowCreate] = useState(false)
  const qc = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['accounts', search, page],
    queryFn: () =>
      client.get<PaginatedResponse<Account>>('/api/accounts', {
        params: { ...(search ? { name: search } : {}), page, itemsPerPage: PER_PAGE },
        headers: { Accept: 'application/ld+json' },
      }).then(r => r.data),
  })

  const deleteMutation = useMutation({
    mutationFn: (id: string) => client.delete(`/api/accounts/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['accounts'] }),
  })

  const confirmDelete = (id: string, name: string) => {
    if (window.confirm(`Delete "${name}"?`)) deleteMutation.mutate(id)
  }

  const accounts = data?.['hydra:member'] ?? []
  const total = data?.['hydra:totalItems'] ?? 0

  return (
    <div>
      <div className={styles.header}>
        <h1 className={styles.title}>Accounts</h1>
        <div className={styles.headerRight}>
          <input
            placeholder="Search accounts..."
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1) }}
            className={styles.searchInput}
          />
          <button onClick={() => setShowCreate(true)} className={styles.newBtn}>+ New Account</button>
        </div>
      </div>

      {isLoading ? <p>Loading...</p> : (
        <>
          <div className={styles.tableWrap}>
            <table className={styles.table}>
              <thead>
                <tr className={styles.theadRow}>
                  <th className={styles.th}>Name</th>
                  <th className={styles.th}>Industry</th>
                  <th className={styles.th}>Country</th>
                  <th className={styles.th}>Employees</th>
                  <th className={styles.th}>Website</th>
                  <th className={styles.th}></th>
                </tr>
              </thead>
              <tbody>
                {accounts.map(a => (
                  <tr key={a.id} className={styles.tbodyRow}>
                    <td className={styles.td}>
                      <Link to={`/accounts/${a.id}`} className={styles.link}>{a.name}</Link>
                    </td>
                    <td className={styles.td}>{a.industry ?? '-'}</td>
                    <td className={styles.td}>{a.country ?? '-'}</td>
                    <td className={styles.td}>{a.employeeCount?.toLocaleString() ?? '-'}</td>
                    <td className={styles.td}>
                      {a.website ? <a href={a.website} target="_blank" rel="noreferrer" className={styles.link}>{a.website}</a> : '-'}
                    </td>
                    <td className={styles.tdRight}>
                      <button onClick={() => confirmDelete(a.id, a.name)} className={styles.delBtn}>Delete</button>
                    </td>
                  </tr>
                ))}
                {accounts.length === 0 && (
                  <tr><td colSpan={6} className={styles.emptyCell}>No accounts found</td></tr>
                )}
              </tbody>
            </table>
          </div>
          <Pagination page={page} totalItems={total} perPage={PER_PAGE} onChange={setPage} />
        </>
      )}

      {showCreate && (
        <Modal title="New Account" onClose={() => setShowCreate(false)}>
          <AccountForm onDone={() => setShowCreate(false)} />
        </Modal>
      )}
    </div>
  )
}
