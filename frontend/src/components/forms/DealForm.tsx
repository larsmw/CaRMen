import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import client from '../../api/client'
import type { Deal, Account, Contact, User, PaginatedResponse } from '../../types'
import styles from './DealForm.module.scss'
import FormError from '../FormError'

interface Props {
  initial?: Partial<Deal>
  onDone: () => void
}

export default function DealForm({ initial, onDone }: Props) {
  const qc = useQueryClient()
  const isEdit = !!initial?.id

  const [form, setForm] = useState({
    title:          initial?.title       ?? '',
    account:        initial?.account?.id         ? `/api/accounts/${initial.account.id}` : '',
    primaryContact: initial?.primaryContact?.id  ? `/api/contacts/${initial.primaryContact.id}` : '',
    owner:          initial?.owner?.id           ? `/api/users/${initial.owner.id}` : '',
    value:          initial?.value       ?? '0',
    currency:       initial?.currency    ?? 'USD',
    stage:          initial?.stage       ?? 'prospecting',
    probability:    initial?.probability?.toString() ?? '50',
    closeDate:      initial?.closeDate   ? initial.closeDate.substring(0, 10) : '',
    description:    initial?.description ?? '',
  })

  const { data: accountsData } = useQuery({
    queryKey: ['accounts-select'],
    queryFn: () => client.get<PaginatedResponse<Account>>('/api/accounts', {
      params: { pagination: false }, headers: { Accept: 'application/ld+json' },
    }).then(r => r.data),
  })
  const { data: contactsData } = useQuery({
    queryKey: ['contacts-select'],
    queryFn: () => client.get<PaginatedResponse<Contact>>('/api/contacts', {
      params: { pagination: false }, headers: { Accept: 'application/ld+json' },
    }).then(r => r.data),
  })
  const { data: usersData } = useQuery({
    queryKey: ['users-select'],
    queryFn: () => client.get<PaginatedResponse<User>>('/api/users', {
      params: { pagination: false }, headers: { Accept: 'application/ld+json' },
    }).then(r => r.data),
  })

  const accounts = accountsData?.['hydra:member'] ?? []
  const contacts = contactsData?.['hydra:member'] ?? []
  const users    = usersData?.['hydra:member']    ?? []

  const set = (k: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) =>
    setForm(f => ({ ...f, [k]: e.target.value }))

  const mutation = useMutation({
    mutationFn: (data: object) => isEdit
      ? client.patch(`/api/deals/${initial!.id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' } })
      : client.post('/api/deals', data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['deals'] })
      if (isEdit) qc.invalidateQueries({ queryKey: ['deal', initial!.id] })
      onDone()
    },
  })

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    mutation.mutate({
      title:          form.title,
      account:        form.account        || null,
      primaryContact: form.primaryContact || null,
      owner:          form.owner          || null,
      value:          form.value,
      currency:       form.currency,
      stage:          form.stage,
      probability:    Number(form.probability),
      closeDate:      form.closeDate      || null,
      description:    form.description    || null,
    })
  }

  return (
    <form onSubmit={submit}>
      <label className={styles.label}>Title *</label>
      <input className={styles.field} value={form.title} onChange={set('title')} required />

      <div className={styles.row}>
        <div>
          <label className={styles.label}>Account (B2B)</label>
          <select className={styles.field} value={form.account} onChange={set('account')}>
            <option value="">— None —</option>
            {accounts.map(a => <option key={a.id} value={`/api/accounts/${a.id}`}>{a.name}</option>)}
          </select>
        </div>
        <div>
          <label className={styles.label}>Contact (B2C)</label>
          <select className={styles.field} value={form.primaryContact} onChange={set('primaryContact')}>
            <option value="">— None —</option>
            {contacts.map(c => <option key={c.id} value={`/api/contacts/${c.id}`}>{c.fullName}</option>)}
          </select>
        </div>
      </div>

      <div className={styles.row}>
        <div>
          <label className={styles.label}>Value</label>
          <input className={styles.field} type="number" min="0" step="0.01" value={form.value} onChange={set('value')} />
        </div>
        <div>
          <label className={styles.label}>Currency</label>
          <select className={styles.field} value={form.currency} onChange={set('currency')}>
            <option>USD</option><option>EUR</option><option>GBP</option><option>DKK</option><option>SEK</option><option>NOK</option>
          </select>
        </div>
      </div>

      <div className={styles.row}>
        <div>
          <label className={styles.label}>Stage</label>
          <select className={styles.field} value={form.stage} onChange={set('stage')}>
            <option value="prospecting">Prospecting</option>
            <option value="qualification">Qualification</option>
            <option value="proposal">Proposal</option>
            <option value="negotiation">Negotiation</option>
            <option value="closed_won">Closed Won</option>
            <option value="closed_lost">Closed Lost</option>
          </select>
        </div>
        <div>
          <label className={styles.label}>Probability (%)</label>
          <input className={styles.field} type="number" min="0" max="100" value={form.probability} onChange={set('probability')} />
        </div>
      </div>

      <div className={styles.row}>
        <div>
          <label className={styles.label}>Close Date</label>
          <input className={styles.field} type="date" value={form.closeDate} onChange={set('closeDate')} />
        </div>
        <div>
          <label className={styles.label}>Owner</label>
          <select className={styles.field} value={form.owner} onChange={set('owner')}>
            <option value="">— None —</option>
            {users.map(u => <option key={u.id} value={`/api/users/${u.id}`}>{u.fullName}</option>)}
          </select>
        </div>
      </div>

      <label className={styles.label}>Description</label>
      <textarea className={styles.textarea} value={form.description} onChange={set('description')} />

      <FormError error={mutation.error} />
      <button type="submit" className={styles.submitBtn} disabled={mutation.isPending}>
        {mutation.isPending ? 'Saving...' : isEdit ? 'Save Changes' : 'Create Deal'}
      </button>
    </form>
  )
}
