import { useEffect, useState, FormEvent } from 'react'
import { useNavigate, Link, useSearchParams } from 'react-router-dom'
import { useAuthStore } from '../store/auth'
import client from '../api/client'
import styles from './Login.module.scss'
import { usePageTitle } from '../hooks/usePageTitle'

export default function Login() {
  usePageTitle('Sign In')
  const [searchParams] = useSearchParams()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  // Public demo: the backend tells us the shared login so visitors can log in with one click
  const [demoLogin, setDemoLogin] = useState<{ email: string; password: string } | null>(null)
  const login = useAuthStore((s) => s.login)
  const navigate = useNavigate()

  // Fresh install: no users yet, so send them to the installer instead
  useEffect(() => {
    client.get('/api/install/status')
      .then(({ data }) => {
        if (!data.installed) navigate('/installer', { replace: true })
        setDemoLogin(data.demoLogin ?? null)
      })
      .catch(() => {})
  }, [navigate])

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
        {demoLogin && (
          <div className={styles.demoBox}>
            <p>
              This is a public demo. Log in as <strong>{demoLogin.email}</strong> / <strong>{demoLogin.password}</strong>.
              Data is reset every night.
            </p>
            <button type="button" className={styles.demoBtn} onClick={() => { setEmail(demoLogin.email); setPassword(demoLogin.password) }}>
              Use demo login
            </button>
          </div>
        )}
        {error && <p className={styles.errorMsg}>{error}</p>}
        <label className={styles.label}>Email</label>
        <input className={styles.input} type="email" value={email} onChange={e => setEmail(e.target.value)} required />
        <label className={styles.label}>Password</label>
        <input className={styles.input} type="password" value={password} onChange={e => setPassword(e.target.value)} required />
        <button type="submit" disabled={loading} className={styles.btn}>
          {loading ? 'Signing in...' : 'Sign in'}
        </button>
        {!demoLogin && (
          <div className={styles.forgotLink}>
            <Link to="/forgot-password">Forgot password?</Link>
          </div>
        )}
      </form>
    </div>
  )
}
