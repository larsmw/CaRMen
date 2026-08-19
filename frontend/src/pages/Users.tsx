import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import client from '../api/client'
import { useAuthStore } from '../store/auth'
import { User, PaginatedResponse } from '../types'
import Modal from '../components/Modal'
import { usePageTitle } from '../hooks/usePageTitle'
import FormError from '../components/FormError'
import Pagination from '../components/Pagination'
import styles from './Users.module.scss'

const PER_PAGE = 20

const ROLES = ['ROLE_USER', 'ROLE_SALES', 'ROLE_MANAGER', 'ROLE_ADMIN']
const ROLE_LABELS: Record<string, string> = {
  ROLE_USER: 'User',
  ROLE_SALES: 'Sales',
  ROLE_MANAGER: 'Manager',
  ROLE_ADMIN: 'Admin',
}

export default function Users() {
  usePageTitle('Users')
  const qc = useQueryClient()
  const currentUser = useAuthStore((s) => s.user)
  const [page, setPage] = useState(1)
  const [editing, setEditing] = useState<User | null>(null)
  const [saveError, setSaveError] = useState<unknown>(null)
  const [showCreate, setShowCreate] = useState(false)

  const { data } = useQuery({
    queryKey: ['users', page],
    queryFn: () =>
      client.get<PaginatedResponse<User>>(`/api/users?page=${page}&itemsPerPage=${PER_PAGE}`)
        .then(r => r.data),
  })

  const users = data?.['hydra:member'] ?? []
  const totalItems = data?.['hydra:totalItems'] ?? 0

  const patchUser = useMutation({
    mutationFn: ({ id, roles, isActive }: { id: string; roles: string[]; isActive: boolean }) =>
      client.patch(`/api/users/${id}`, { roles, isActive }, {
        headers: { 'Content-Type': 'application/merge-patch+json' },
      }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['users'] })
      setEditing(null)
      setSaveError(null)
    },
    onError: (e) => setSaveError(e),
  })

  return (
    <div>
      <div className={styles.titleRow}>
        <h1 className={styles.title}>Users</h1>
        <button className={styles.createBtn} onClick={() => setShowCreate(true)}>+ New User</button>
      </div>

      <div className={styles.tableWrap}>
        <table className={styles.table}>
          <thead>
            <tr>
              {['Name', 'Email', 'Role', 'Active', ''].map(h => (
                <th key={h} className={styles.th}>{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {users.map(u => (
              <tr key={u.id} className={styles.tbodyRow}>
                <td className={styles.td}>{u.fullName}</td>
                <td className={styles.td}>{u.email}</td>
                <td className={styles.td}>{roleLabels(u.roles)}</td>
                <td className={styles.td}>{u.isActive ? 'Yes' : 'No'}</td>
                <td className={styles.td}>
                  <button onClick={() => { setEditing(u); setSaveError(null) }} className={styles.editBtn}>Edit</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Pagination page={page} totalItems={totalItems} perPage={PER_PAGE} onChange={setPage} />

      {showCreate && (
        <Modal title="New User" onClose={() => setShowCreate(false)}>
          <UserCreateForm onDone={() => { setShowCreate(false); qc.invalidateQueries({ queryKey: ['users'] }) }} />
        </Modal>
      )}

      {editing && (
        <Modal title={`Edit: ${editing.fullName}`} onClose={() => setEditing(null)}>
          <UserEditForm
            user={editing}
            isSelf={editing.id === currentUser?.id}
            saving={patchUser.isPending}
            error={saveError}
            onSave={(roles, isActive) =>
              patchUser.mutate({ id: editing.id, roles, isActive })
            }
          />
        </Modal>
      )}
    </div>
  )
}

function UserEditForm({
  user,
  isSelf,
  saving,
  error,
  onSave,
}: {
  user: User
  isSelf: boolean
  saving: boolean
  error: unknown
  onSave: (roles: string[], isActive: boolean) => void
}) {
  const [roles, setRoles] = useState<string[]>(user.roles.filter(r => ROLES.includes(r)))
  const [isActive, setIsActive] = useState(user.isActive)

  const toggleRole = (r: string) =>
    setRoles(prev => prev.includes(r) ? prev.filter(x => x !== r) : [...prev, r])

  return (
    <div>
      <label className={styles.formLabel}>Roles</label>
      <div className={styles.checkboxGroup}>
        {ROLES.map(r => (
          <label key={r} className={styles.formLabelCheckbox}>
            <input
              type="checkbox"
              checked={roles.includes(r)}
              onChange={() => toggleRole(r)}
            />
            {ROLE_LABELS[r]}
          </label>
        ))}
      </div>

      <label className={styles.formLabelCheckbox}>
        <input
          type="checkbox"
          checked={isActive}
          disabled={isSelf}
          onChange={e => setIsActive(e.target.checked)}
          title={isSelf ? 'You cannot deactivate your own account' : undefined}
        />
        Active {isSelf && <span style={{ fontSize: '0.8em', color: '#94a3b8' }}>(cannot deactivate yourself)</span>}
      </label>

      {error && <FormError error={error} />}

      <button
        onClick={() => onSave(roles, isActive)}
        disabled={saving || roles.length === 0}
        className={styles.saveBtn}
      >
        {saving ? 'Saving…' : 'Save changes'}
      </button>
    </div>
  )
}

function UserCreateForm({ onDone }: { onDone: () => void }) {
  const [form, setForm] = useState({ firstName: '', lastName: '', email: '', password: '' })
  const [roles, setRoles] = useState<string[]>(['ROLE_USER'])
  const set = (k: string) => (e: React.ChangeEvent<HTMLInputElement>) =>
    setForm(f => ({ ...f, [k]: e.target.value }))

  const toggleRole = (r: string) =>
    setRoles(prev => prev.includes(r) ? prev.filter(x => x !== r) : [...prev, r])

  const mutation = useMutation({
    mutationFn: () => client.post('/api/users', {
      firstName: form.firstName,
      lastName:  form.lastName,
      email:     form.email,
      plainPassword: form.password,
      roles,
    }),
    onSuccess: onDone,
  })

  return (
    <form onSubmit={e => { e.preventDefault(); mutation.mutate() }}>
      <div className={styles.formRow}>
        <div>
          <label className={styles.formLabel}>First Name *</label>
          <input className={styles.formInput} value={form.firstName} onChange={set('firstName')} required />
        </div>
        <div>
          <label className={styles.formLabel}>Last Name *</label>
          <input className={styles.formInput} value={form.lastName} onChange={set('lastName')} required />
        </div>
      </div>

      <label className={styles.formLabel}>Email *</label>
      <input className={styles.formInput} type="email" value={form.email} onChange={set('email')} required />

      <label className={styles.formLabel}>Password *</label>
      <input className={styles.formInput} type="password" value={form.password} onChange={set('password')} required minLength={8} />

      <label className={styles.formLabel}>Roles</label>
      <div className={styles.checkboxGroup}>
        {ROLES.map(r => (
          <label key={r} className={styles.formLabelCheckbox}>
            <input
              type="checkbox"
              checked={roles.includes(r)}
              onChange={() => toggleRole(r)}
            />
            {ROLE_LABELS[r]}
          </label>
        ))}
      </div>

      <FormError error={mutation.error} />
      <button type="submit" disabled={mutation.isPending || roles.length === 0} className={styles.saveBtn}>
        {mutation.isPending ? 'Creating…' : 'Create User'}
      </button>
    </form>
  )
}

function roleLabels(roles: string[]): string {
  const labels = ROLES.filter(r => roles.includes(r)).map(r => ROLE_LABELS[r])
  return labels.length > 0 ? labels.join(', ') : 'User'
}

function topRoleKey(roles: string[]): string {
  for (const r of ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_SALES', 'ROLE_USER']) {
    if (roles.includes(r)) return r
  }
  return 'ROLE_USER'
}
