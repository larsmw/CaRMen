import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import client from '../api/client'
import type { Contact, PaginatedResponse } from '../types'
import { usePageTitle } from '../hooks/usePageTitle'
import Modal from '../components/Modal'
import ContactForm from '../components/forms/ContactForm'
import Pagination from '../components/Pagination'
import styles from './Contacts.module.scss'

const PER_PAGE = 20

const statusClass: Record<string, string> = {
  lead:     'statusLead',
  prospect: 'statusProspect',
  customer: 'statusCustomer',
  inactive: 'statusInactive',
}

export default function Contacts() {
  usePageTitle('Contacts')
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [showCreate, setShowCreate] = useState(false)
  const qc = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['contacts', search, page],
    queryFn: () =>
      client.get<PaginatedResponse<Contact>>('/api/contacts', {
        params: { ...(search ? { firstName: search } : {}), page, itemsPerPage: PER_PAGE },
        headers: { Accept: 'application/ld+json' },
      }).then(r => r.data),
  })

  const deleteMutation = useMutation({
    mutationFn: (id: string) => client.delete(`/api/contacts/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['contacts'] }),
  })

  const confirmDelete = (id: string, name: string) => {
    if (window.confirm(`Delete "${name}"?`)) deleteMutation.mutate(id)
  }

  const contacts = data?.['hydra:member'] ?? []
  const total = data?.['hydra:totalItems'] ?? 0

  return (
    <div>
      <div className={styles.header}>
        <h1 className={styles.title}>Contacts</h1>
        <div className={styles.headerRight}>
          <input
            placeholder="Search..."
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1) }}
            className={styles.searchInput}
          />
          <button onClick={() => setShowCreate(true)} className={styles.newBtn}>+ New Contact</button>
        </div>
      </div>

      {isLoading ? <p>Loading...</p> : (
        <>
          <div className={styles.tableWrap}>
            <table className={styles.table}>
              <thead>
                <tr className={styles.theadRow}>
                  <th className={styles.th}>Name</th>
                  <th className={styles.th}>Email</th>
                  <th className={styles.th}>Job Title</th>
                  <th className={styles.th}>Account</th>
                  <th className={styles.th}>Status</th>
                  <th className={styles.th}></th>
                </tr>
              </thead>
              <tbody>
                {contacts.map(c => (
                  <tr key={c.id} className={styles.tbodyRow}>
                    <td className={styles.td}>
                      <Link to={`/contacts/${c.id}`} className={styles.link}>{c.fullName}</Link>
                    </td>
                    <td className={styles.td}>{c.email ?? '-'}</td>
                    <td className={styles.td}>{c.jobTitle ?? '-'}</td>
                    <td className={styles.td}>{c.account?.name ?? '-'}</td>
                    <td className={styles.td}>
                      <span className={`${styles.badge} ${styles[statusClass[c.status]] ?? ''}`}>{c.status}</span>
                    </td>
                    <td className={styles.tdRight}>
                      <button onClick={() => confirmDelete(c.id, c.fullName)} className={styles.delBtn}>Delete</button>
                    </td>
                  </tr>
                ))}
                {contacts.length === 0 && (
                  <tr><td colSpan={6} className={styles.emptyCell}>No contacts found</td></tr>
                )}
              </tbody>
            </table>
          </div>
          <Pagination page={page} totalItems={total} perPage={PER_PAGE} onChange={setPage} />
        </>
      )}

      {showCreate && (
        <Modal title="New Contact" onClose={() => setShowCreate(false)}>
          <ContactForm onDone={() => setShowCreate(false)} />
        </Modal>
      )}
    </div>
  )
}
