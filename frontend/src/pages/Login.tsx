import { useState, FormEvent } from 'react'
import { useNavigate, Link, useSearchParams } from 'react-router-dom'
import { useAuthStore } from '../store/auth'
import styles from './Login.module.scss'

export default function Login() {
  const [searchParams] = useSearchParams()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  const login = useAuthStore((s) => s.login)
  const navigate = useNavigate()

  const submit = async (e: FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError(null)
    try {
      await login(email, password)
      navigate('/')
    } catch {
      setError('Invalid credentials')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className={styles.wrapper}>
      <form onSubmit={submit} className={styles.form}>
        <h1 className={styles.title}>Sign in to CRM</h1>
        {searchParams.get('reset') && (
          <p className={styles.successMsg}>Password updated. You can now sign in.</p>
        )}
        {error && <p className={styles.errorMsg}>{error}</p>}
        <label className={styles.label}>Email</label>
        <input className={styles.input} type="email" value={email} onChange={e => setEmail(e.target.value)} required />
        <label className={styles.label}>Password</label>
        <input className={styles.input} type="password" value={password} onChange={e => setPassword(e.target.value)} required />
        <button type="submit" disabled={loading} className={styles.btn}>
          {loading ? 'Signing in...' : 'Sign in'}
        </button>
        <div className={styles.forgotLink}>
          <Link to="/forgot-password">Forgot password?</Link>
        </div>
      </form>
    </div>
  )
}
