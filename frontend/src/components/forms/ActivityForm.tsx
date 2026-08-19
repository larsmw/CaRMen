import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import client from '../../api/client'
import type { Activity, Contact, Deal, User, PaginatedResponse } from '../../types'
import styles from './ActivityForm.module.scss'
import FormError from '../FormError'

interface Props {
  initial?: Partial<Activity>
  onDone: () => void
}

export default function ActivityForm({ initial, onDone }: Props) {
  const qc = useQueryClient()
  const isEdit = !!initial?.id

  const [form, setForm] = useState({
    type:        initial?.type        ?? 'call',
    subject:     initial?.subject     ?? '',
    description: initial?.description ?? '',
    status:      initial?.status      ?? 'planned',
    scheduledAt: initial?.scheduledAt ? initial.scheduledAt.substring(0, 16) : '',
    contact:     initial?.contact?.id     ? `/api/contacts/${initial.contact.id}` : '',
    deal:        initial?.deal?.id        ? `/api/deals/${initial.deal.id}` : '',
    assignedTo:  initial?.assignedTo?.id  ? `/api/users/${initial.assignedTo.id}` : '',
  })

  const { data: contactsData } = useQuery({
    queryKey: ['contacts-select'],
    queryFn: () => client.get<PaginatedResponse<Contact>>('/api/contacts', {
      params: { pagination: false }, headers: { Accept: 'application/ld+json' },
    }).then(r => r.data),
  })
  const { data: dealsData } = useQuery({
    queryKey: ['deals-select'],
    queryFn: () => client.get<PaginatedResponse<Deal>>('/api/deals', {
      params: { pagination: false }, headers: { Accept: 'application/ld+json' },
    }).then(r => r.data),
  })
  const { data: usersData } = useQuery({
    queryKey: ['users-select'],
    queryFn: () => client.get<PaginatedResponse<User>>('/api/users', {
      params: { pagination: false }, headers: { Accept: 'application/ld+json' },
    }).then(r => r.data),
  })

  const contacts = contactsData?.['hydra:member'] ?? []
  const deals    = dealsData?.['hydra:member']    ?? []
  const users    = usersData?.['hydra:member']    ?? []

  const set = (k: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) =>
    setForm(f => ({ ...f, [k]: e.target.value }))

  const mutation = useMutation({
    mutationFn: (data: object) => isEdit
      ? client.patch(`/api/activities/${initial!.id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' } })
      : client.post('/api/activities', data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['activities'] })
      if (isEdit) qc.invalidateQueries({ queryKey: ['activity', initial!.id] })
      onDone()
    },
  })

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    mutation.mutate({
      type:        form.type,
      subject:     form.subject,
      description: form.description || null,
      status:      form.status,
      scheduledAt: form.scheduledAt  || null,
      contact:     form.contact      || null,
      deal:        form.deal         || null,
      assignedTo:  form.assignedTo   || null,
    })
  }

  return (
    <form onSubmit={submit}>
      <div className={styles.row}>
        <div>
          <label className={styles.label}>Type *</label>
          <select className={styles.field} value={form.type} onChange={set('type')}>
            <option value="call">Call</option>
            <option value="email">Email</option>
            <option value="meeting">Meeting</option>
            <option value="task">Task</option>
            <option value="note">Note</option>
          </select>
        </div>
        <div>
          <label className={styles.label}>Status</label>
          <select className={styles.field} value={form.status} onChange={set('status')}>
            <option value="planned">Planned</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      <label className={styles.label}>Subject *</label>
      <input className={styles.field} value={form.subject} onChange={set('subject')} required />

      <label className={styles.label}>Scheduled At</label>
      <input className={styles.field} type="datetime-local" value={form.scheduledAt} onChange={set('scheduledAt')} />

      <div className={styles.row}>
        <div>
          <label className={styles.label}>Contact</label>
          <select className={styles.field} value={form.contact} onChange={set('contact')}>
            <option value="">— None —</option>
            {contacts.map(c => <option key={c.id} value={`/api/contacts/${c.id}`}>{c.fullName}</option>)}
          </select>
        </div>
        <div>
          <label className={styles.label}>Deal</label>
          <select className={styles.field} value={form.deal} onChange={set('deal')}>
            <option value="">— None —</option>
            {deals.map(d => <option key={d.id} value={`/api/deals/${d.id}`}>{d.title}</option>)}
          </select>
        </div>
      </div>

      <label className={styles.label}>Assigned To</label>
      <select className={styles.field} value={form.assignedTo} onChange={set('assignedTo')}>
        <option value="">— None —</option>
        {users.map(u => <option key={u.id} value={`/api/users/${u.id}`}>{u.fullName}</option>)}
      </select>

      <label className={styles.label}>Description</label>
      <textarea className={styles.textarea} value={form.description} onChange={set('description')} />

      <FormError error={mutation.error} />
      <button type="submit" className={styles.submitBtn} disabled={mutation.isPending}>
        {mutation.isPending ? 'Saving...' : isEdit ? 'Save Changes' : 'Create Activity'}
      </button>
    </form>
  )
}
