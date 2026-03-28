import { useState, FormEvent } from 'react'
import { Link, useSearchParams, useNavigate } from 'react-router-dom'
import client from '../api/client'
import styles from './ResetPassword.module.scss'

export default function ResetPassword() {
  const [params] = useSearchParams()
  const navigate = useNavigate()
  const token = params.get('token') ?? ''

  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  const submit = async (e: FormEvent) => {
    e.preventDefault()
    if (password !== confirm) { setError('Passwords do not match.'); return }
    setLoading(true)
    setError(null)
    try {
      await client.post('/api/reset-password', { token, password })
      navigate('/login?reset=1')
    } catch (err: unknown) {
      const msg = (err as { response?: { data?: { error?: string } } })?.response?.data?.error
      setError(msg ?? 'Something went wrong.')
    } finally {
      setLoading(false)
    }
  }

  if (!token) {
    return (
      <div className={styles.wrapper}>
        <div className={styles.box}>
          <p className={styles.invalidMsg}>Invalid reset link.</p>
          <Link to="/login" className={styles.link}>Back to sign in</Link>
        </div>
      </div>
    )
  }

  return (
    <div className={styles.wrapper}>
      <div className={styles.box}>
        <h1 className={styles.title}>Set new password</h1>
        <p className={styles.subText}>Choose a password with at least 8 characters.</p>
        <form onSubmit={submit}>
          <label className={styles.label}>New password</label>
          <input className={styles.input} type="password" value={password} onChange={e => setPassword(e.target.value)} required minLength={8} autoFocus />
          <label className={styles.label}>Confirm password</label>
          <input className={styles.input} type="password" value={confirm} onChange={e => setConfirm(e.target.value)} required minLength={8} />
          {error && <p className={styles.errorMsg}>{error}</p>}
          <button type="submit" className={styles.btn} disabled={loading}>
            {loading ? 'Saving...' : 'Set password'}
          </button>
        </form>
      </div>
    </div>
  )
}
