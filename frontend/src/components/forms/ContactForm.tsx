import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import client from '../../api/client'
import type { Contact, Account, PaginatedResponse } from '../../types'
import styles from './ContactForm.module.scss'
import FormError from '../FormError'

interface Props {
  initial?: Partial<Contact>
  onDone: () => void
}

export default function ContactForm({ initial, onDone }: Props) {
  const qc = useQueryClient()
  const isEdit = !!initial?.id

  const [form, setForm] = useState({
    firstName:  initial?.firstName  ?? '',
    lastName:   initial?.lastName   ?? '',
    email:      initial?.email      ?? '',
    phone:      initial?.phone      ?? '',
    mobile:     initial?.mobile     ?? '',
    jobTitle:   initial?.jobTitle   ?? '',
    department: initial?.department ?? '',
    status:       initial?.status       ?? 'lead',
    notes:        initial?.notes        ?? '',
    account:      initial?.account?.id ? `/api/accounts/${initial.account.id}` : '',
    addressLine1: initial?.addressLine1 ?? '',
    addressLine2: initial?.addressLine2 ?? '',
    postalCode:   initial?.postalCode   ?? '',
    city:         initial?.city         ?? '',
    country:      initial?.country      ?? '',
  })

  const { data: accountsData } = useQuery({
    queryKey: ['accounts-select'],
    queryFn: () => client.get<PaginatedResponse<Account>>('/api/accounts', {
      params: { pagination: false },
      headers: { Accept: 'application/ld+json' },
    }).then(r => r.data),
  })
  const accounts = accountsData?.['hydra:member'] ?? []

  const set = (k: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) =>
    setForm(f => ({ ...f, [k]: e.target.value }))

  const mutation = useMutation({
    mutationFn: (data: object) => isEdit
      ? client.patch(`/api/contacts/${initial!.id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' } })
      : client.post('/api/contacts', data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['contacts'] })
      if (isEdit) qc.invalidateQueries({ queryKey: ['contact', initial!.id] })
      onDone()
    },
  })

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    mutation.mutate({
      firstName:  form.firstName,
      lastName:   form.lastName,
      email:      form.email      || null,
      phone:      form.phone      || null,
      mobile:     form.mobile     || null,
      jobTitle:   form.jobTitle   || null,
      department: form.department || null,
      status:       form.status,
      notes:        form.notes        || null,
      account:      form.account      || null,
      addressLine1: form.addressLine1 || null,
      addressLine2: form.addressLine2 || null,
      postalCode:   form.postalCode   || null,
      city:         form.city         || null,
      country:      form.country      || null,
    })
  }

  return (
    <form onSubmit={submit}>
      <div className={styles.row}>
        <div>
          <label className={styles.label}>First Name *</label>
          <input className={styles.field} value={form.firstName} onChange={set('firstName')} required />
        </div>
        <div>
          <label className={styles.label}>Last Name *</label>
          <input className={styles.field} value={form.lastName} onChange={set('lastName')} required />
        </div>
      </div>

      <label className={styles.label}>Email</label>
      <input className={styles.field} type="email" value={form.email} onChange={set('email')} />

      <div className={styles.row}>
        <div>
          <label className={styles.label}>Phone</label>
          <input className={styles.field} value={form.phone} onChange={set('phone')} />
        </div>
        <div>
          <label className={styles.label}>Mobile</label>
          <input className={styles.field} value={form.mobile} onChange={set('mobile')} />
        </div>
      </div>

      <div className={styles.row}>
        <div>
          <label className={styles.label}>Job Title</label>
          <input className={styles.field} value={form.jobTitle} onChange={set('jobTitle')} />
        </div>
        <div>
          <label className={styles.label}>Department</label>
          <input className={styles.field} value={form.department} onChange={set('department')} />
        </div>
      </div>

      <div className={styles.row}>
        <div>
          <label className={styles.label}>Account</label>
          <select className={styles.field} value={form.account} onChange={set('account')}>
            <option value="">— None —</option>
            {accounts.map(a => (
              <option key={a.id} value={`/api/accounts/${a.id}`}>{a.name}</option>
            ))}
          </select>
        </div>
        <div>
          <label className={styles.label}>Status</label>
          <select className={styles.field} value={form.status} onChange={set('status')}>
            <option value="lead">Lead</option>
            <option value="prospect">Prospect</option>
            <option value="customer">Customer</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <label className={styles.label}>Address</label>
      <input className={styles.field} placeholder="Line 1" value={form.addressLine1} onChange={set('addressLine1')} />
      <input className={styles.field} placeholder="Line 2" value={form.addressLine2} onChange={set('addressLine2')} />
      <div className={styles.row}>
        <div>
          <label className={styles.label}>Postal Code</label>
          <input className={styles.field} value={form.postalCode} onChange={set('postalCode')} />
        </div>
        <div>
          <label className={styles.label}>City</label>
          <input className={styles.field} value={form.city} onChange={set('city')} />
        </div>
      </div>
      <label className={styles.label}>Country</label>
      <input className={styles.field} value={form.country} onChange={set('country')} />

      <label className={styles.label}>Notes</label>
      <textarea className={styles.textarea} value={form.notes} onChange={set('notes')} />

      <FormError error={mutation.error} />
      <button type="submit" className={styles.submitBtn} disabled={mutation.isPending}>
        {mutation.isPending ? 'Saving...' : isEdit ? 'Save Changes' : 'Create Contact'}
      </button>
    </form>
  )
}
