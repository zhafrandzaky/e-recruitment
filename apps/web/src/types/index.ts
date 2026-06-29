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

export type ApplicationStatus = 'pending' | 'shortlisted' | 'rejected'

export interface Application {
  id: string
  job_posting_id: string
  status: ApplicationStatus
  cv_original_filename: string | null
  additional_data: Record<string, string> | null
  applied_at: string | null
  created_at: string
  updated_at: string
  job?: {
    id: string
    title: string
    location: string | null
    status: string
  }
  applicant?: {
    id: string
    name: string
    email: string
  }
  status_history?: ApplicationStatusHistoryEntry[]
}

export interface ApplicationStatusHistoryEntry {
  id: string
  previous_status: ApplicationStatus | null
  new_status: ApplicationStatus
  changed_at: string
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

export interface Interview {
  id: string
  scheduled_at: string
  meeting_link: string
  status: 'scheduled' | 'completed' | 'cancelled'
}
