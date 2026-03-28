export interface User {
  id: string
  email: string
  firstName: string
  lastName: string
  fullName: string
  roles: string[]
  isActive: boolean
  createdAt: string
}

export interface Account {
  id: string
  name: string
  industry: string | null
  website: string | null
  phone: string | null
  city: string | null
  country: string | null
  employeeCount: number | null
  annualRevenue: string | null
  description: string | null
  createdAt: string
  updatedAt: string
}

export interface Contact {
  id: string
  firstName: string
  lastName: string
  fullName: string
  email: string | null
  phone: string | null
  mobile: string | null
  jobTitle: string | null
  department: string | null
  account: Pick<Account, 'id' | 'name'> | null
  status: 'lead' | 'prospect' | 'customer' | 'inactive'
  notes: string | null
  addressLine1: string | null
  addressLine2: string | null
  postalCode: string | null
  city: string | null
  country: string | null
  createdAt: string
  updatedAt: string
}

export interface Deal {
  id: string
  title: string
  account: Pick<Account, 'id' | 'name'> | null
  primaryContact: Pick<Contact, 'id' | 'fullName'> | null
  owner: Pick<User, 'id' | 'fullName'> | null
  value: string
  currency: string
  stage: DealStage
  probability: number
  closeDate: string | null
  description: string | null
  lostReason: string | null
  createdAt: string
  updatedAt: string
}

export type DealStage =
  | 'prospecting'
  | 'qualification'
  | 'proposal'
  | 'negotiation'
  | 'closed_won'
  | 'closed_lost'

export interface Activity {
  id: string
  type: 'call' | 'email' | 'meeting' | 'task' | 'note'
  subject: string
  description: string | null
  status: 'planned' | 'completed' | 'cancelled'
  scheduledAt: string | null
  completedAt: string | null
  contact: Pick<Contact, 'id' | 'fullName'> | null
  deal: Pick<Deal, 'id' | 'title'> | null
  assignedTo: Pick<User, 'id' | 'fullName'> | null
  createdAt: string
}

export interface PaginatedResponse<T> {
  'hydra:member': T[]
  'hydra:totalItems': number
}

export interface DashboardStats {
  contacts: number
  accounts: number
  open_deals: number
  pending_tasks: number
  pipeline: Array<{ stage: DealStage; count: number; total_value: string }>
}
