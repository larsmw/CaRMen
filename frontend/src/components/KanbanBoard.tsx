import { useState } from 'react'
import {
  DndContext,
  DragEndEvent,
  DragOverlay,
  DragStartEvent,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core'
import { useDroppable, useDraggable } from '@dnd-kit/core'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import client from '../api/client'
import type { Deal, DealStage, PaginatedResponse } from '../types'
import styles from './KanbanBoard.module.scss'

const STAGES: DealStage[] = [
  'prospecting', 'qualification', 'proposal', 'negotiation', 'closed_won', 'closed_lost',
]

const STAGE_LABELS: Record<DealStage, string> = {
  prospecting:   'Prospecting',
  qualification: 'Qualification',
  proposal:      'Proposal',
  negotiation:   'Negotiation',
  closed_won:    'Closed Won',
  closed_lost:   'Closed Lost',
}

function DealCard({ deal, ghost = false }: { deal: Deal; ghost?: boolean }) {
  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({ id: deal.id })

  return (
    <div
      ref={setNodeRef}
      {...attributes}
      {...listeners}
      className={[
        styles.card,
        isDragging ? styles.cardDragging : '',
        ghost ? styles.cardGhost : '',
      ].join(' ')}
    >
      <Link
        to={`/deals/${deal.id}`}
        className={styles.cardTitle}
        onClick={e => e.stopPropagation()}
      >
        {deal.title}
      </Link>
      {(deal.account || deal.primaryContact) && (
        <div className={styles.cardCustomer}>
          {deal.account?.name ?? deal.primaryContact?.fullName}
        </div>
      )}
      <div className={styles.cardFooter}>
        <span className={styles.cardValue}>
          ${Number(deal.value).toLocaleString()} {deal.currency}
        </span>
        {deal.closeDate && (
          <span className={styles.cardDate}>
            {new Date(deal.closeDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
          </span>
        )}
      </div>
    </div>
  )
}

function Column({ stage, deals }: { stage: DealStage; deals: Deal[] }) {
  const { setNodeRef, isOver } = useDroppable({ id: stage })
  const total = deals.reduce((sum, d) => sum + Number(d.value), 0)

  return (
    <div className={styles.column}>
      <div className={styles.columnHeader}>
        <span className={`${styles.columnLabel} ${styles[`stage_${stage}`]}`}>
          {STAGE_LABELS[stage]}
        </span>
        <span className={styles.columnCount}>{deals.length}</span>
      </div>
      {total > 0 && (
        <div className={styles.columnTotal}>${total.toLocaleString()}</div>
      )}
      <div
        ref={setNodeRef}
        className={`${styles.columnBody} ${isOver ? styles.columnBodyOver : ''}`}
      >
        {deals.map(deal => (
          <DealCard key={deal.id} deal={deal} />
        ))}
        {deals.length === 0 && (
          <div className={styles.emptyDrop}>Drop here</div>
        )}
      </div>
    </div>
  )
}

export default function KanbanBoard() {
  const [activeId, setActiveId] = useState<string | null>(null)
  const qc = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['deals-kanban'],
    queryFn: () =>
      client.get<PaginatedResponse<Deal>>('/api/deals', {
        params: { itemsPerPage: 500 },
        headers: { Accept: 'application/ld+json' },
      }).then(r => r.data),
  })

  const stageMutation = useMutation({
    mutationFn: ({ id, stage }: { id: string; stage: DealStage }) =>
      client.patch(`/api/deals/${id}`, { stage }, {
        headers: { 'Content-Type': 'application/merge-patch+json' },
      }),
    onMutate: async ({ id, stage }) => {
      await qc.cancelQueries({ queryKey: ['deals-kanban'] })
      const prev = qc.getQueryData<PaginatedResponse<Deal>>(['deals-kanban'])
      qc.setQueryData<PaginatedResponse<Deal>>(['deals-kanban'], old =>
        old
          ? { ...old, 'hydra:member': old['hydra:member'].map(d => d.id === id ? { ...d, stage } : d) }
          : old
      )
      return { prev }
    },
    onError: (_err, _vars, ctx) => {
      if (ctx?.prev) qc.setQueryData(['deals-kanban'], ctx.prev)
    },
    onSettled: () => {
      qc.invalidateQueries({ queryKey: ['deals'] })
      qc.invalidateQueries({ queryKey: ['dashboard'] })
    },
  })

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 5 } })
  )

  const deals = data?.['hydra:member'] ?? []
  const byStage = STAGES.reduce((acc, s) => {
    acc[s] = deals.filter(d => d.stage === s)
    return acc
  }, {} as Record<DealStage, Deal[]>)

  const activeDeal = activeId ? deals.find(d => d.id === activeId) ?? null : null

  function handleDragStart(event: DragStartEvent) {
    setActiveId(String(event.active.id))
  }

  function handleDragEnd(event: DragEndEvent) {
    setActiveId(null)
    const { active, over } = event
    if (!over) return
    const newStage = over.id as DealStage
    const deal = deals.find(d => d.id === active.id)
    if (deal && deal.stage !== newStage) {
      stageMutation.mutate({ id: deal.id, stage: newStage })
    }
  }

  if (isLoading) return <p>Loading...</p>

  return (
    <DndContext sensors={sensors} onDragStart={handleDragStart} onDragEnd={handleDragEnd}>
      <div className={styles.board}>
        {STAGES.map(stage => (
          <Column key={stage} stage={stage} deals={byStage[stage]} />
        ))}
      </div>
      <DragOverlay dropAnimation={null}>
        {activeDeal && <DealCard deal={activeDeal} ghost />}
      </DragOverlay>
    </DndContext>
  )
}
