import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import client from '../api/client'
import { User, PaginatedResponse } from '../types'
import Modal from '../components/Modal'
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
  const qc = useQueryClient()
  const [page, setPage] = useState(1)
  const [editing, setEditing] = useState<User | null>(null)
  const [saveError, setSaveError] = useState<unknown>(null)

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
      <h1 className={styles.title}>Users</h1>

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
                <td className={styles.td}>{topRoleLabel(u.roles)}</td>
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

      {editing && (
        <Modal title={`Edit: ${editing.fullName}`} onClose={() => setEditing(null)}>
          <UserEditForm
            user={editing}
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
  saving,
  error,
  onSave,
}: {
  user: User
  saving: boolean
  error: unknown
  onSave: (roles: string[], isActive: boolean) => void
}) {
  const [role, setRole] = useState(topRoleKey(user.roles))
  const [isActive, setIsActive] = useState(user.isActive)

  return (
    <div>
      <label className={styles.formLabel}>Role</label>
      <select value={role} onChange={e => setRole(e.target.value)} className={styles.formInput}>
        {ROLES.map(r => <option key={r} value={r}>{ROLE_LABELS[r]}</option>)}
      </select>

      <label className={styles.formLabelCheckbox}>
        <input type="checkbox" checked={isActive} onChange={e => setIsActive(e.target.checked)} />
        Active
      </label>

      {error && <FormError error={error} />}

      <button
        onClick={() => onSave([role], isActive)}
        disabled={saving}
        className={styles.saveBtn}
      >
        {saving ? 'Saving…' : 'Save changes'}
      </button>
    </div>
  )
}

function topRoleLabel(roles: string[]): string {
  return ROLE_LABELS[topRoleKey(roles)] ?? 'User'
}

function topRoleKey(roles: string[]): string {
  for (const r of ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_SALES', 'ROLE_USER']) {
    if (roles.includes(r)) return r
  }
  return 'ROLE_USER'
}
