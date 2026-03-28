import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useParams, Link, useNavigate } from 'react-router-dom'
import client from '../api/client'
import type { Account, Contact, Deal, PaginatedResponse } from '../types'
import Modal from '../components/Modal'
import AccountForm from '../components/forms/AccountForm'
import styles from './AccountDetail.module.scss'
import { usePageTitle } from '../hooks/usePageTitle'

export default function AccountDetail() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [showEdit, setShowEdit] = useState(false)

  const { data: account, isLoading } = useQuery({
    queryKey: ['account', id],
    queryFn: () => client.get<Account>(`/api/accounts/${id}`).then(r => r.data),
  })
  usePageTitle(account?.name ?? 'Account')

  const { data: contactsData } = useQuery({
    queryKey: ['account-contacts', id],
    queryFn: () => client.get<PaginatedResponse<Contact>>('/api/contacts', {
      params: { 'account.id': id }, headers: { Accept: 'application/ld+json' },
    }).then(r => r.data),
    enabled: !!id,
  })

  const { data: dealsData } = useQuery({
    queryKey: ['account-deals', id],
    queryFn: () => client.get<PaginatedResponse<Deal>>('/api/deals', {
      params: { 'account.id': id }, headers: { Accept: 'application/ld+json' },
    }).then(r => r.data),
    enabled: !!id,
  })

  const deleteMutation = useMutation({
    mutationFn: () => client.delete(`/api/accounts/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['accounts'] }); navigate('/accounts') },
  })

  if (isLoading) return <p>Loading...</p>
  if (!account) return <p>Account not found.</p>

  const contacts = contactsData?.['hydra:member'] ?? []
  const deals    = dealsData?.['hydra:member']    ?? []

  return (
    <div>
      <div>
        <Link to="/accounts" className={styles.backLink}>&larr; Accounts</Link>
      </div>

      <div className={styles.pageHeader}>
        <div>
          <h1 className={styles.pageTitle}>{account.name}</h1>
          {account.industry && <p className={styles.subtitle}>{account.industry}</p>}
        </div>
        <div className={styles.actions}>
          <button className={styles.editBtn} onClick={() => setShowEdit(true)}>Edit</button>
          <button className={styles.deleteBtn} onClick={() => { if (window.confirm(`Delete "${account.name}"?`)) deleteMutation.mutate() }}>Delete</button>
        </div>
      </div>

      <div className={styles.grid2}>
        <div className={styles.card}>
          <h2 className={styles.sectionTitle}>Details</h2>
          <Field label="Website" value={account.website ? <a href={account.website} target="_blank" rel="noreferrer" style={{ color: '#3b82f6' }}>{account.website}</a> : null} />
          <Field label="Phone" value={account.phone} />
          <Field label="City" value={account.city} />
          <Field label="Country" value={account.country} />
          <Field label="Employees" value={account.employeeCount?.toLocaleString()} />
          <Field label="Revenue" value={account.annualRevenue ? `$${Number(account.annualRevenue).toLocaleString()}` : null} />
        </div>
        <div className={styles.card}>
          <h2 className={styles.sectionTitle}>Description</h2>
          <p className={account.description ? styles.description : styles.descriptionEmpty}>
            {account.description ?? 'No description.'}
          </p>
        </div>
      </div>

      <div className={styles.grid2}>
        <div className={styles.card}>
          <h2 className={styles.sectionTitle}>Contacts ({contacts.length})</h2>
          {contacts.length === 0 ? <p className={styles.empty}>No contacts.</p> : contacts.map(c => (
            <Link key={c.id} to={`/contacts/${c.id}`} className={styles.listItem}>
              <span>{c.fullName}</span>
              <span className={styles.listItemMeta}>{c.jobTitle ?? ''}</span>
            </Link>
          ))}
        </div>
        <div className={styles.card}>
          <h2 className={styles.sectionTitle}>Deals ({deals.length})</h2>
          {deals.length === 0 ? <p className={styles.empty}>No deals.</p> : deals.map(d => (
            <Link key={d.id} to={`/deals/${d.id}`} className={styles.listItem}>
              <span>{d.title}</span>
              <span className={styles.listItemMeta}>${Number(d.value).toLocaleString()}</span>
            </Link>
          ))}
        </div>
      </div>

      {showEdit && (
        <Modal title="Edit Account" onClose={() => setShowEdit(false)}>
          <AccountForm initial={account} onDone={() => setShowEdit(false)} />
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
