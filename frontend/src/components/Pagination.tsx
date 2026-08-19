import styles from './Pagination.module.scss'

interface Props {
  page: number
  totalItems: number
  perPage: number
  onChange: (page: number) => void
}

export default function Pagination({ page, totalItems, perPage, onChange }: Props) {
  const totalPages = Math.ceil(totalItems / perPage)
  if (totalPages <= 1) return null

  const pages = Array.from({ length: totalPages }, (_, i) => i + 1)

  return (
    <div className={styles.wrapper}>
      <span className={styles.info}>
        {(page - 1) * perPage + 1}–{Math.min(page * perPage, totalItems)} of {totalItems}
      </span>
      <div className={styles.controls}>
        <button className={styles.btn} disabled={page === 1} onClick={() => onChange(page - 1)}>
          &larr;
        </button>
        {pages.map(p => (
          <button
            key={p}
            className={`${p === page ? styles.btnActive : styles.btn} ${styles.pageBtn}`}
            onClick={() => onChange(p)}
          >
            {p}
          </button>
        ))}
        <button className={styles.btn} disabled={page === totalPages} onClick={() => onChange(page + 1)}>
          &rarr;
        </button>
      </div>
    </div>
  )
}
