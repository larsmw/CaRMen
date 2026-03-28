import { useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import client from '../../api/client'
import type { Account } from '../../types'
import styles from './AccountForm.module.scss'
import FormError from '../FormError'

interface Props {
  initial?: Partial<Account>
  onDone: () => void
}

export default function AccountForm({ initial, onDone }: Props) {
  const qc = useQueryClient()
  const isEdit = !!initial?.id

  const [form, setForm] = useState({
    name:          initial?.name          ?? '',
    industry:      initial?.industry      ?? '',
    website:       initial?.website       ?? '',
    phone:         initial?.phone         ?? '',
    city:          initial?.city          ?? '',
    country:       initial?.country       ?? '',
    employeeCount: initial?.employeeCount?.toString() ?? '',
    annualRevenue: initial?.annualRevenue ?? '',
    description:   initial?.description  ?? '',
  })

  const set = (k: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
    setForm(f => ({ ...f, [k]: e.target.value }))

  const mutation = useMutation({
    mutationFn: (data: object) => isEdit
      ? client.patch(`/api/accounts/${initial!.id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' } })
      : client.post('/api/accounts', data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['accounts'] })
      if (isEdit) qc.invalidateQueries({ queryKey: ['account', initial!.id] })
      onDone()
    },
  })

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    mutation.mutate({
      name:          form.name,
      industry:      form.industry      || null,
      website:       form.website       || null,
      phone:         form.phone         || null,
      city:          form.city          || null,
      country:       form.country       || null,
      employeeCount: form.employeeCount ? Number(form.employeeCount) : null,
      annualRevenue: form.annualRevenue || null,
      description:   form.description  || null,
    })
  }

  return (
    <form onSubmit={submit}>
      <label className={styles.label}>Name *</label>
      <input className={styles.field} value={form.name} onChange={set('name')} required />

      <div className={styles.row}>
        <div>
          <label className={styles.label}>Industry</label>
          <input className={styles.field} value={form.industry} onChange={set('industry')} />
        </div>
        <div>
          <label className={styles.label}>Phone</label>
          <input className={styles.field} value={form.phone} onChange={set('phone')} />
        </div>
      </div>

      <label className={styles.label}>Website</label>
      <input className={styles.field} type="url" value={form.website} onChange={set('website')} placeholder="https://" />

      <div className={styles.row}>
        <div>
          <label className={styles.label}>City</label>
          <input className={styles.field} value={form.city} onChange={set('city')} />
        </div>
        <div>
          <label className={styles.label}>Country</label>
          <input className={styles.field} value={form.country} onChange={set('country')} />
        </div>
      </div>

      <div className={styles.row}>
        <div>
          <label className={styles.label}>Employees</label>
          <input className={styles.field} type="number" value={form.employeeCount} onChange={set('employeeCount')} />
        </div>
        <div>
          <label className={styles.label}>Annual Revenue</label>
          <input className={styles.field} type="number" value={form.annualRevenue} onChange={set('annualRevenue')} />
        </div>
      </div>

      <label className={styles.label}>Description</label>
      <textarea className={styles.textarea} value={form.description} onChange={set('description')} />

      <FormError error={mutation.error} />
      <button type="submit" className={styles.submitBtn} disabled={mutation.isPending}>
        {mutation.isPending ? 'Saving...' : isEdit ? 'Save Changes' : 'Create Account'}
      </button>
    </form>
  )
}
