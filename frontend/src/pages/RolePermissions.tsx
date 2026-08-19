import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import client from '../api/client'
import styles from './RolePermissions.module.scss'
import { usePageTitle } from '../hooks/usePageTitle'

const ROLES = ['ROLE_USER', 'ROLE_SALES', 'ROLE_MANAGER', 'ROLE_ADMIN']

const ROLE_LABELS: Record<string, string> = {
  ROLE_USER: 'User',
  ROLE_SALES: 'Sales',
  ROLE_MANAGER: 'Manager',
  ROLE_ADMIN: 'Admin',
}

const ALL_PERMISSIONS = [
  'CONTACT_CREATE', 'CONTACT_EDIT', 'CONTACT_DELETE',
  'ACCOUNT_CREATE', 'ACCOUNT_EDIT', 'ACCOUNT_DELETE',
  'DEAL_CREATE', 'DEAL_EDIT', 'DEAL_DELETE',
  'ACTIVITY_DELETE',
]

const PERMISSION_LABELS: Record<string, string> = {
  CONTACT_CREATE: 'Create contacts',
  CONTACT_EDIT: 'Edit contacts',
  CONTACT_DELETE: 'Delete contacts',
  ACCOUNT_CREATE: 'Create accounts',
  ACCOUNT_EDIT: 'Edit accounts',
  ACCOUNT_DELETE: 'Delete accounts',
  DEAL_CREATE: 'Create deals',
  DEAL_EDIT: 'Edit deals',
  DEAL_DELETE: 'Delete deals',
  ACTIVITY_DELETE: 'Delete activities',
}

interface RolePerms { role: string; permissions: string[] }

export default function RolePermissions() {
  usePageTitle('Role Permissions')
  const qc = useQueryClient()
  const [isSaving, setIsSaving] = useState(false)
  const [saved, setSaved] = useState(false)

  const { data = [], isLoading } = useQuery({
    queryKey: ['role-permissions'],
    queryFn: () => client.get<RolePerms[]>('/api/role-permissions').then(r => r.data),
  })

  // local state: role → Set of permissions
  const [local, setLocal] = useState<Record<string, string[]>>({})

  // merge server data into local once loaded (only if no local edits yet)
  const serverMap: Record<string, string[]> = {}
  for (const rp of data) serverMap[rp.role] = rp.permissions
  const effective = (role: string): string[] => local[role] ?? serverMap[role] ?? []

  const toggle = (role: string, perm: string) => {
    const current = effective(role)
    setLocal(prev => ({
      ...prev,
      [role]: current.includes(perm) ? current.filter(p => p !== perm) : [...current, perm],
    }))
  }

  const isDirty = ROLES.some(role => {
    const loc = local[role]
    if (!loc) return false
    const srv = serverMap[role] ?? []
    return JSON.stringify([...loc].sort()) !== JSON.stringify([...srv].sort())
  })

  const saveAll = useMutation({
    mutationFn: async () => {
      for (const role of ROLES) {
        if (role === 'ROLE_ADMIN') continue
        const perms = effective(role)
        await client.put(`/api/role-permissions/${role}`, { permissions: perms })
      }
    },
    onMutate: () => setIsSaving(true),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['role-permissions'] })
      setLocal({})
      setIsSaving(false)
      setSaved(true)
      setTimeout(() => setSaved(false), 2000)
    },
    onError: () => setIsSaving(false),
  })

  if (isLoading) return <p>Loading…</p>

  return (
    <div>
      <div className={styles.pageHeader}>
        <h1 className={styles.title}>Role Permissions</h1>
        <div className={styles.headerRight}>
          {saved && <span className={styles.savedMsg}>Saved</span>}
          <button
            onClick={() => saveAll.mutate()}
            disabled={isSaving || !isDirty}
            className={styles.saveBtn}
          >
            {isSaving ? 'Saving…' : 'Save changes'}
          </button>
        </div>
      </div>
      <p className={styles.subText}>
        Roles inherit from lower roles: Admin &gt; Manager &gt; Sales &gt; User.
        Admin always has all permissions.
      </p>

      <div className={styles.tableWrap}>
        <table className={styles.table}>
          <thead>
            <tr className={styles.theadRow}>
              <th className={styles.thLeft}>Permission</th>
              {ROLES.map(role => (
                <th key={role} className={styles.thCenter}>{ROLE_LABELS[role]}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {ALL_PERMISSIONS.map((perm, i) => (
              <tr key={perm} className={i % 2 === 0 ? styles.tbodyRowEven : styles.tbodyRowOdd}>
                <td className={styles.tdPerm}>{PERMISSION_LABELS[perm]}</td>
                {ROLES.map(role => {
                  const isAdmin = role === 'ROLE_ADMIN'
                  const checked = isAdmin || effective(role).includes(perm)
                  return (
                    <td key={role} className={styles.tdCenter}>
                      <input
                        type="checkbox"
                        checked={checked}
                        disabled={isAdmin}
                        onChange={() => toggle(role, perm)}
                        className={isAdmin ? styles.checkboxDisabled : styles.checkbox}
                      />
                    </td>
                  )
                })}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
