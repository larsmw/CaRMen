import { useEffect, useState, FormEvent } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import client from '../api/client'
import styles from './Installer.module.scss'
import { usePageTitle } from '../hooks/usePageTitle'

interface SystemCheck {
  phpVersion: string
  minPhpVersion: string
  phpVersionOk: boolean
  missingExtensions: string[]
  dbConnectionOk: boolean
  dbError: string | null
  pendingMigrations: number | null
  systemOk: boolean
}

interface AdminForm {
  email: string
  firstName: string
  lastName: string
  password: string
  demoData: boolean
}

const STEPS = ['System check', 'Database', 'Admin user', 'Done']

function errorMessage(err: any, fallback: string): string {
  return err?.response?.data?.error ?? fallback
}

export default function Installer() {
  usePageTitle('Install')
  const navigate = useNavigate()
  const [step, setStep] = useState(0)
  const [check, setCheck] = useState<SystemCheck | null>(null)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})
  const [admin, setAdmin] = useState<AdminForm>({ email: '', firstName: '', lastName: '', password: '', demoData: false })

  const runCheck = async () => {
    setBusy(true)
    setError(null)
    try {
      const { data } = await client.get<SystemCheck>('/api/install/system-check')
      setCheck(data)
    } catch (err) {
      if ((err as any)?.response?.status === 403) {
        navigate('/login', { replace: true })
        return
      }
      setError(errorMessage(err, 'Could not run the system check.'))
    } finally {
      setBusy(false)
    }
  }

  useEffect(() => {
    client.get('/api/install/status')
      .then(({ data }) => (data.installed ? navigate('/login', { replace: true }) : runCheck()))
      .catch(() => runCheck())
  }, [])

  const migrate = async () => {
    setBusy(true)
    setError(null)
    try {
      await client.post('/api/install/migrate')
      setStep(2)
    } catch (err) {
      setError(errorMessage(err, 'Creating the database tables failed.'))
    } finally {
      setBusy(false)
    }
  }

  const createAdmin = async (e: FormEvent) => {
    e.preventDefault()
    setBusy(true)
    setError(null)
    setFieldErrors({})
    try {
      await client.post('/api/install/create-admin', admin)
      setStep(3)
    } catch (err: any) {
      if (err?.response?.status === 422) {
        setFieldErrors(err.response.data.errors ?? {})
      } else {
        setError(errorMessage(err, 'Could not create the admin user.'))
      }
    } finally {
      setBusy(false)
    }
  }

  const field = (name: Exclude<keyof AdminForm, 'demoData'>, label: string, type = 'text') => (
    <>
      <label className={styles.label} htmlFor={`admin-${name}`}>{label}</label>
      <input
        id={`admin-${name}`}
        className={styles.input}
        type={type}
        value={admin[name]}
        onChange={e => setAdmin(a => ({ ...a, [name]: e.target.value }))}
        minLength={name === 'password' ? 8 : undefined}
        required
      />
      {fieldErrors[name] && <p className={styles.fieldError}>{fieldErrors[name]}</p>}
    </>
  )

  const renderStep = () => {
    switch (step) {
      case 0:
        return (
          <>
            <h2 className={styles.stepTitle}>System check</h2>
            {!check && busy && <p className={styles.muted}>Checking…</p>}
            {check && (
              <ul className={styles.checks}>
                <li className={check.phpVersionOk ? styles.ok : styles.fail}>
                  PHP {check.phpVersion} (needs {check.minPhpVersion} or newer)
                </li>
                <li className={check.missingExtensions.length ? styles.fail : styles.ok}>
                  {check.missingExtensions.length
                    ? `Missing PHP extensions: ${check.missingExtensions.join(', ')}`
                    : 'All required PHP extensions loaded'}
                </li>
                <li className={check.dbConnectionOk ? styles.ok : styles.fail}>
                  {check.dbConnectionOk ? 'Database connection OK' : 'Cannot connect to the database'}
                  {check.dbError && <span className={styles.detail}>{check.dbError}</span>}
                </li>
              </ul>
            )}
            {check && !check.systemOk && (
              <p className={styles.muted}>
                Fix the problems above (database settings come from <code>DATABASE_URL</code>), then check again.
              </p>
            )}
            {check?.systemOk ? (
              <button className={styles.btn} onClick={() => setStep(1)}>Continue</button>
            ) : (
              <button className={styles.btn} onClick={runCheck} disabled={busy}>
                {busy ? 'Checking…' : 'Check again'}
              </button>
            )}
          </>
        )

      case 1:
        return (
          <>
            <h2 className={styles.stepTitle}>Database</h2>
            <p className={styles.muted}>
              {check?.pendingMigrations
                ? `${check.pendingMigrations} migration(s) will be run to create the database tables.`
                : 'The database schema is already up to date.'}
            </p>
            <button className={styles.btn} onClick={migrate} disabled={busy}>
              {busy ? 'Creating tables…' : check?.pendingMigrations ? 'Create database tables' : 'Continue'}
            </button>
          </>
        )

      case 2:
        return (
          <form onSubmit={createAdmin}>
            <h2 className={styles.stepTitle}>Create admin user</h2>
            {field('email', 'Email', 'email')}
            {field('firstName', 'First name')}
            {field('lastName', 'Last name')}
            {field('password', 'Password', 'password')}
            <label className={styles.checkboxLabel}>
              <input
                type="checkbox"
                checked={admin.demoData}
                onChange={e => setAdmin(a => ({ ...a, demoData: e.target.checked }))}
              />
              <span>
                Load demo data
                <span className={styles.detail}>Sample accounts, contacts, deals and activities to try things out.</span>
              </span>
            </label>
            <button type="submit" className={styles.btn} disabled={busy}>
              {busy ? 'Creating…' : 'Create admin user'}
            </button>
          </form>
        )

      default:
        return (
          <>
            <h2 className={styles.stepTitle}>Installation complete</h2>
            <p className={styles.success}>
              Your CRM is ready{admin.demoData ? ', with demo data loaded' : ''}. Sign in with the admin account you just created.
            </p>
            <Link to="/login" className={styles.btn}>Go to sign in</Link>
          </>
        )
    }
  }

  return (
    <div className={styles.wrapper}>
      <div className={styles.box}>
        <h1 className={styles.title}>Install CRM</h1>
        <ol className={styles.steps}>
          {STEPS.map((label, i) => (
            <li key={label} className={i === step ? styles.current : i < step ? styles.done : undefined}>
              <span className={styles.stepNumber}>{i + 1}</span>
              <span className={styles.stepLabel}>{label}</span>
            </li>
          ))}
        </ol>
        {error && <p className={styles.errorMsg}>{error}</p>}
        {renderStep()}
      </div>
    </div>
  )
}
