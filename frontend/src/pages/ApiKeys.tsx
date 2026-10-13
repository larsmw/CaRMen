import { useState, FormEvent } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import client from '../api/client'
import Modal from '../components/Modal'
import FormError from '../components/FormError'
import { usePageTitle } from '../hooks/usePageTitle'
import { useLocale } from '../context/LocaleContext'
import styles from './ApiKeys.module.scss'

interface ApiKey {
  id: string
  name: string
  prefix: string
  scopes: string[]
  createdBy: string | null
  createdAt: string
  lastUsedAt: string | null
  revokedAt: string | null
}

type CreatedKey = ApiKey & { key: string }

// Where integrations send leads: same origin and base path as this app (/api or /demo/api)
const LEADS_URL = `${window.location.origin}${import.meta.env.BASE_URL}api/leads`

export default function ApiKeys() {
  usePageTitle('API Keys')
  const qc = useQueryClient()
  const { locale } = useLocale()
  const [showCreate, setShowCreate] = useState(false)
  const [created, setCreated] = useState<CreatedKey | null>(null)

  const { data: keys = [], isLoading } = useQuery({
    queryKey: ['api-keys'],
    queryFn: () => client.get<ApiKey[]>('/api/api-keys').then(r => r.data),
  })

  const revoke = useMutation({
    mutationFn: (id: string) => client.delete(`/api/api-keys/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['api-keys'] }),
  })

  const when = (iso: string | null) =>
    iso ? new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso)) : '—'

  const confirmRevoke = (key: ApiKey) => {
    if (window.confirm(`Revoke "${key.name}"? Anything using this key stops working immediately.`)) {
      revoke.mutate(key.id)
    }
  }

  return (
    <div>
      <div className={styles.titleRow}>
        <h1 className={styles.title}>API Keys</h1>
        <button className={styles.createBtn} onClick={() => setShowCreate(true)}>+ New API key</button>
      </div>
      <p className={styles.intro}>
        Keys let your website forms and other systems send leads into the CRM. A key can only create leads;
        it can't read or change anything else.
      </p>

      <div className={styles.tableWrap}>
        <table className={styles.table}>
          <thead>
            <tr>
              {['Name', 'Key', 'Created', 'Last used', 'Status', ''].map(h => <th key={h} className={styles.th}>{h}</th>)}
            </tr>
          </thead>
          <tbody>
            {keys.map(k => (
              <tr key={k.id} className={k.revokedAt ? styles.revokedRow : styles.row}>
                <td className={styles.td}>{k.name}</td>
                <td className={styles.td}><code>{k.prefix}</code></td>
                <td className={styles.td}>{when(k.createdAt)}{k.createdBy && <div className={styles.muted}>by {k.createdBy}</div>}</td>
                <td className={styles.td}>{when(k.lastUsedAt)}</td>
                <td className={styles.td}>{k.revokedAt ? `Revoked ${when(k.revokedAt)}` : 'Active'}</td>
                <td className={styles.td}>
                  {!k.revokedAt && (
                    <button className={styles.revokeBtn} onClick={() => confirmRevoke(k)} disabled={revoke.isPending}>
                      Revoke
                    </button>
                  )}
                </td>
              </tr>
            ))}
            {!isLoading && keys.length === 0 && (
              <tr><td className={styles.td} colSpan={6}>No API keys yet.</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {showCreate && (
        <Modal title="New API key" onClose={() => setShowCreate(false)}>
          <CreateKeyForm onCreated={key => {
            setShowCreate(false)
            setCreated(key)
            qc.invalidateQueries({ queryKey: ['api-keys'] })
          }} />
        </Modal>
      )}

      {created && (
        <Modal title={`API key "${created.name}"`} onClose={() => setCreated(null)}>
          <ShowKeyOnce created={created} onDone={() => setCreated(null)} />
        </Modal>
      )}
    </div>
  )
}

function CreateKeyForm({ onCreated }: { onCreated: (key: CreatedKey) => void }) {
  const [name, setName] = useState('')
  const mutation = useMutation({
    mutationFn: () => client.post<CreatedKey>('/api/api-keys', { name }).then(r => r.data),
    onSuccess: onCreated,
  })

  const submit = (e: FormEvent) => {
    e.preventDefault()
    mutation.mutate()
  }

  return (
    <form onSubmit={submit}>
      <label className={styles.formLabel} htmlFor="api-key-name">Name</label>
      <input
        id="api-key-name"
        className={styles.formInput}
        value={name}
        onChange={e => setName(e.target.value)}
        placeholder="e.g. Website contact form"
        maxLength={100}
        required
        autoFocus
      />
      <p className={styles.muted}>Use one key per website or system, so you can revoke them separately.</p>
      {!!mutation.error && <FormError error={mutation.error} />}
      <button type="submit" className={styles.saveBtn} disabled={mutation.isPending}>
        {mutation.isPending ? 'Creating…' : 'Create key'}
      </button>
    </form>
  )
}

function ShowKeyOnce({ created, onDone }: { created: CreatedKey; onDone: () => void }) {
  const [copied, setCopied] = useState(false)
  const copy = async () => {
    await navigator.clipboard.writeText(created.key)
    setCopied(true)
  }

  const example = `curl -X POST ${LEADS_URL} \\
  -H 'X-Api-Key: ${created.key}' \\
  -H 'Content-Type: application/json' \\
  -d '{"firstName": "Ada", "lastName": "Lovelace", "email": "ada@example.com",
       "company": "Analytical Engines", "message": "Please call me", "source": "Website contact form"}'`

  return (
    <div>
      <p className={styles.warning}>Copy this key now. For your security it won't be shown again.</p>
      <div className={styles.keyBox}>
        <code data-testid="new-api-key">{created.key}</code>
        <button type="button" className={styles.copyBtn} onClick={copy}>{copied ? 'Copied' : 'Copy'}</button>
      </div>

      <h3 className={styles.subTitle}>Sending a lead</h3>
      <p className={styles.muted}>
        <code>POST {LEADS_URL}</code> with the key in the <code>X-Api-Key</code> header. Required: <code>email</code> and
        <code> firstName</code> + <code>lastName</code> (or <code>name</code>). Optional: <code>phone</code>,
        <code> company</code>, <code>jobTitle</code>, <code>message</code>, <code>source</code>. A new email address
        becomes a contact with status "lead"; a known one gets the submission added as a note.
      </p>
      <pre className={styles.example}>{example}</pre>

      <button type="button" className={styles.saveBtn} onClick={onDone}>Done</button>
    </div>
  )
}
