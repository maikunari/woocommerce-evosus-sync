/**
 * Task Management Types
 * JSON-based task system for project management
 */

export type TaskStatus = "not_started" | "in_progress" | "blocked" | "complete"
export type TaskAssignee = "planner" | "worker" | "human"
export type RiskLevel = "🟢" | "🟡" | "🔴"
export type CommentAuthor = "planner" | "worker" | "human"

export interface TaskComment {
  timestamp: string // ISO 8601 datetime
  author: CommentAuthor
  text: string
}

export interface Subtask {
  id: string
  name: string
  status: TaskStatus
  assignee: TaskAssignee
  risk: RiskLevel
  dependencies: string[] // Array of subtask IDs that must complete first
  context: string
  prompt: string
  success_criteria: string[]
  files_modified: string[] // Array of file paths modified during this task (auto-populated)
  started_at: string | null // ISO 8601 timestamp when status changed to in_progress (auto-populated)
  completed_at: string | null // ISO 8601 timestamp when status changed to complete (auto-populated)
  comments: TaskComment[]
}

export interface Phase {
  id: string
  name: string
  status: TaskStatus
  overview: string
  created: string // ISO 8601 datetime
  subtasks: Subtask[]
}

export interface TasksDatabase {
  phases: Phase[]
}
