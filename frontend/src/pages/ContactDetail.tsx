import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useParams, Link, useNavigate } from 'react-router-dom'
import client from '../api/client'
import type { Contact, Activity, Deal, PaginatedResponse } from '../types'
import Modal from '../components/Modal'
import ContactForm from '../components/forms/ContactForm'
import styles from './ContactDetail.module.scss'

const statusClass: Record<string, string> = {
  lead:     'statusLead',
  prospect: 'statusProspect',
  customer: 'statusCustomer',
  inactive: 'statusInactive',
}

export default function ContactDetail() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [showEdit, setShowEdit] = useState(false)

  const { data: contact, isLoading } = useQuery({
    queryKey: ['contact', id],
    queryFn: () => client.get<Contact>(`/api/contacts/${id}`).then(r => r.data),
  })

  const { data: activitiesData } = useQuery({
    queryKey: ['contact-activities', id],
    queryFn: () => client.get<PaginatedResponse<Activity>>('/api/activities', {
      params: { 'contact.id': id }, headers: { Accept: 'application/ld+json' },
    }).then(r => r.data),
    enabled: !!id,
  })

  const { data: dealsData } = useQuery({
    queryKey: ['contact-deals', id],
    queryFn: () => client.get<PaginatedResponse<Deal>>('/api/deals', {
      params: { 'primaryContact.id': id }, headers: { Accept: 'application/ld+json' },
    }).then(r => r.data),
    enabled: !!id,
  })

  const deleteMutation = useMutation({
    mutationFn: () => client.delete(`/api/contacts/${id}`),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['contacts'] }); navigate('/contacts') },
  })

  if (isLoading) return <p>Loading...</p>
  if (!contact) return <p>Contact not found.</p>

  const activities = activitiesData?.['hydra:member'] ?? []
  const deals      = dealsData?.['hydra:member']      ?? []

  return (
    <div>
      <div>
        <Link to="/contacts" className={styles.backLink}>&larr; Contacts</Link>
      </div>

      <div className={styles.pageHeader}>
        <div>
          <h1 className={styles.pageTitle}>{contact.fullName}</h1>
          {contact.jobTitle && (
            <p className={styles.subtitle}>{contact.jobTitle}{contact.department ? ` · ${contact.department}` : ''}</p>
          )}
        </div>
        <div className={styles.actions}>
          <span className={`${styles.badge} ${styles[statusClass[contact.status]] ?? ''}`}>{contact.status}</span>
          <button className={styles.editBtn} onClick={() => setShowEdit(true)}>Edit</button>
          <button className={styles.deleteBtn} onClick={() => { if (window.confirm(`Delete "${contact.fullName}"?`)) deleteMutation.mutate() }}>Delete</button>
        </div>
      </div>

      <div className={styles.grid2}>
        <div className={styles.card}>
          <h2 className={styles.sectionTitle}>Details</h2>
          <Field label="Email" value={contact.email} />
          <Field label="Phone" value={contact.phone} />
          <Field label="Mobile" value={contact.mobile} />
          <Field label="Account" value={contact.account ? (
            <Link to={`/accounts/${contact.account.id}`} style={{ color: '#3b82f6' }}>{contact.account.name}</Link>
          ) : null} />
          {(contact.addressLine1 || contact.city) && (
            <Field label="Address" value={[contact.addressLine1, contact.addressLine2, contact.postalCode, contact.city, contact.country].filter(Boolean).join(', ')} />
          )}
        </div>
        <div className={styles.card}>
          <h2 className={styles.sectionTitle}>Notes</h2>
          <p className={contact.notes ? styles.notes : styles.notesEmpty}>
            {contact.notes ?? 'No notes yet.'}
          </p>
        </div>
      </div>

      <div className={styles.grid2}>
        <div className={styles.card}>
          <h2 className={styles.sectionTitle}>Deals ({deals.length})</h2>
          {deals.length === 0 ? <p className={styles.empty}>No deals.</p> : deals.map(d => (
            <Link key={d.id} to={`/deals/${d.id}`} className={styles.listItem}>
              <span>{d.title}</span>
              <span className={styles.listItemMeta}>${Number(d.value).toLocaleString()}</span>
            </Link>
          ))}
        </div>
        <div className={styles.card}>
          <h2 className={styles.sectionTitle}>Activities ({activities.length})</h2>
          {activities.length === 0 ? <p className={styles.empty}>No activities.</p> : activities.map(a => (
            <Link key={a.id} to={`/activities/${a.id}`} className={styles.listItem}>
              <span>{a.subject}</span>
              <span className={styles.listItemMeta}>{a.type}</span>
            </Link>
          ))}
        </div>
      </div>

      {showEdit && (
        <Modal title="Edit Contact" onClose={() => setShowEdit(false)}>
          <ContactForm initial={contact} onDone={() => setShowEdit(false)} />
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
