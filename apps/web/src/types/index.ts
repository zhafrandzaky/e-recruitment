export type UserRole = 'applicant' | 'hr_admin'

export interface User {
  id: string
  name: string
  email: string
  role: UserRole
}

export interface JobPosting {
  id: string
  title: string
  description?: string
  qualifications?: string
  location: string | null
  deadline: string | null
  status: 'draft' | 'active' | 'closed'
  created_by?: string
  created_at: string
  updated_at?: string
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    page: number
    per_page: number
    total: number
  }
}

export interface ApiError {
  code: string
  message: string
  fields?: Record<string, string[]>
}
