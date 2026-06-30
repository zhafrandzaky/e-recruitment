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

export type ApplicationStatus = 'pending' | 'shortlisted' | 'rejected' | 'hired'

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

export interface ChatMessage {
  id: string
  content: string
  sender_id: string
  sent_at: string
  sender?: {
    id: string
    name: string | null
  }
}

// ─── Reporting (FR-018) ─────────────────────────────────────────────────────

export interface ApplicantsPerJob {
  job_id: string
  job_title: string
  count: number
}

/** Distribution of applications across the four status stages. */
export type StatusFunnel = Record<ApplicationStatus, number>

export interface ReportOverview {
  applicants_per_job: ApplicantsPerJob[]
  funnel: StatusFunnel
  /** Null when no application has been hired yet. */
  avg_time_to_hire_days: number | null
}

export interface JobFunnel {
  job_id: string
  job_title: string
  funnel: StatusFunnel
  total: number
}
