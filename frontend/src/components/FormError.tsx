import type { AxiosError } from 'axios'
import styles from './FormError.module.scss'

interface ApiViolation {
  propertyPath: string
  message: string
}

interface ApiError {
  'hydra:description'?: string
  'hydra:title'?: string
  violations?: ApiViolation[]
  detail?: string
}

export default function FormError({ error }: { error: unknown }) {
  if (!error) return null

  const axiosError = error as AxiosError<ApiError>
  const data = axiosError.response?.data

  if (!data) {
    return <p className={styles.error}>{axiosError.message}</p>
  }

  // Validation violations (422)
  if (data.violations?.length) {
    return (
      <div className={styles.errorList}>
        {data.violations.map((v, i) => (
          <p key={i} className={styles.error}>
            {v.propertyPath ? <strong>{v.propertyPath}: </strong> : null}
            {v.message}
          </p>
        ))}
      </div>
    )
  }

  // Hydra error description
  const message = data['hydra:description'] ?? data.detail ?? 'Something went wrong.'
  return <p className={styles.error}>{message}</p>
}
