import { useState, FormEvent } from 'react'
import { Link } from 'react-router-dom'
import client from '../api/client'
import styles from './ForgotPassword.module.scss'

export default function ForgotPassword() {
  const [email, setEmail] = useState('')
  const [sent, setSent] = useState(false)
  const [loading, setLoading] = useState(false)

  const submit = async (e: FormEvent) => {
    e.preventDefault()
    setLoading(true)
    await client.post('/api/forgot-password', { email }).catch(() => {})
    setSent(true)
    setLoading(false)
  }

  return (
    <div className={styles.wrapper}>
      <div className={styles.box}>
        <h1 className={styles.title}>Forgot password</h1>

        {sent ? (
          <>
            <p className={styles.sentText}>
              If that email address is registered you will receive a reset link shortly. Check your inbox — and your spam folder.
            </p>
            <Link to="/login" className={styles.link}>Back to sign in</Link>
          </>
        ) : (
          <>
            <p className={styles.subText}>
              Enter your email and we'll send you a reset link.
            </p>
            <form onSubmit={submit}>
              <label className={styles.label}>Email</label>
              <input
                className={styles.input}
                type="email"
                value={email}
                onChange={e => setEmail(e.target.value)}
                required
                autoFocus
              />
              <button type="submit" className={styles.btn} disabled={loading}>
                {loading ? 'Sending...' : 'Send reset link'}
              </button>
            </form>
            <div className={styles.backLink}>
              <Link to="/login" className={styles.link}>Back to sign in</Link>
            </div>
          </>
        )}
      </div>
    </div>
  )
}
