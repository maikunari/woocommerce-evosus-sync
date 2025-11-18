/**
 * Task Manager
 * Helper functions for managing tasks in tasks.json
 */

import fs from 'fs'
import path from 'path'
import type {
  TasksDatabase,
  Phase,
  Subtask,
  TaskStatus,
  CommentAuthor,
  TaskComment
} from './task-types'

const TASKS_FILE = path.join(process.cwd(), 'tasks.json')

/**
 * Read tasks from tasks.json
 */
function readTasks(): TasksDatabase {
  try {
    const data = fs.readFileSync(TASKS_FILE, 'utf-8')
    return JSON.parse(data) as TasksDatabase
  } catch (error) {
    console.error('Error reading tasks.json:', error)
    return { phases: [] }
  }
}

/**
 * Write tasks to tasks.json
 */
function writeTasks(db: TasksDatabase): void {
  try {
    fs.writeFileSync(TASKS_FILE, JSON.stringify(db, null, 2), 'utf-8')
  } catch (error) {
    console.error('Error writing tasks.json:', error)
    throw error
  }
}

/**
 * List all tasks with their status
 */
export function listTasks(): void {
  const db = readTasks()

  console.log('\n📋 Task List\n')

  if (db.phases.length === 0) {
    console.log('No tasks found.\n')
    return
  }

  db.phases.forEach(phase => {
    const statusIcon = getStatusIcon(phase.status)
    console.log(`\n${statusIcon} ${phase.name} [${phase.status}]`)
    console.log(`   ID: ${phase.id}`)
    console.log(`   ${phase.overview}`)

    if (phase.subtasks.length > 0) {
      console.log(`   Subtasks:`)
      phase.subtasks.forEach(subtask => {
        const subtaskIcon = getStatusIcon(subtask.status)
        console.log(`     ${subtaskIcon} ${subtask.id}. ${subtask.name} [${subtask.assignee}] ${subtask.risk}`)
      })
    }
  })

  console.log('\n')
}

/**
 * Get a specific task by phase ID and subtask ID
 */
export function getTask(phaseId: string, subtaskId?: string): Phase | Subtask | null {
  const db = readTasks()
  const phase = db.phases.find(p => p.id === phaseId)

  if (!phase) {
    console.log(`Phase not found: ${phaseId}`)
    return null
  }

  if (!subtaskId) {
    return phase
  }

  const subtask = phase.subtasks.find(s => s.id === subtaskId)

  if (!subtask) {
    console.log(`Subtask not found: ${subtaskId} in phase ${phaseId}`)
    return null
  }

  return subtask
}

/**
 * Update task status
 */
export function updateTaskStatus(
  phaseId: string,
  status: TaskStatus,
  subtaskId?: string
): boolean {
  const db = readTasks()
  const phaseIndex = db.phases.findIndex(p => p.id === phaseId)

  if (phaseIndex === -1) {
    console.log(`Phase not found: ${phaseId}`)
    return false
  }

  if (subtaskId) {
    // Update subtask status
    const subtaskIndex = db.phases[phaseIndex].subtasks.findIndex(s => s.id === subtaskId)

    if (subtaskIndex === -1) {
      console.log(`Subtask not found: ${subtaskId} in phase ${phaseId}`)
      return false
    }

    db.phases[phaseIndex].subtasks[subtaskIndex].status = status
    console.log(`✓ Updated ${phaseId}/${subtaskId} status to: ${status}`)
  } else {
    // Update phase status
    db.phases[phaseIndex].status = status
    console.log(`✓ Updated ${phaseId} status to: ${status}`)
  }

  writeTasks(db)
  return true
}

/**
 * Add a comment to a task
 */
export function addComment(
  phaseId: string,
  author: CommentAuthor,
  text: string,
  subtaskId?: string
): boolean {
  const db = readTasks()
  const phaseIndex = db.phases.findIndex(p => p.id === phaseId)

  if (phaseIndex === -1) {
    console.log(`Phase not found: ${phaseId}`)
    return false
  }

  const comment: TaskComment = {
    timestamp: new Date().toISOString(),
    author,
    text
  }

  if (subtaskId) {
    // Add comment to subtask
    const subtaskIndex = db.phases[phaseIndex].subtasks.findIndex(s => s.id === subtaskId)

    if (subtaskIndex === -1) {
      console.log(`Subtask not found: ${subtaskId} in phase ${phaseId}`)
      return false
    }

    db.phases[phaseIndex].subtasks[subtaskIndex].comments.push(comment)
    console.log(`✓ Added comment to ${phaseId}/${subtaskId}`)
  } else {
    // Note: Phase-level comments not currently supported in type definition
    // Could extend Phase type to include comments if needed
    console.log(`Phase-level comments not supported. Add comment to a subtask.`)
    return false
  }

  writeTasks(db)
  return true
}

/**
 * Get tasks for worker (assignee="worker", status != "complete")
 */
export function getTasksForWorker(): Array<{ phase: Phase; subtask: Subtask }> {
  const db = readTasks()
  const workerTasks: Array<{ phase: Phase; subtask: Subtask }> = []

  db.phases.forEach(phase => {
    phase.subtasks.forEach(subtask => {
      if (subtask.assignee === 'worker' && subtask.status !== 'complete') {
        workerTasks.push({ phase, subtask })
      }
    })
  })

  return workerTasks
}

/**
 * Display tasks for worker
 */
export function listWorkerTasks(): void {
  const tasks = getTasksForWorker()

  console.log('\n🔧 Worker Tasks (assignee=worker, not complete)\n')

  if (tasks.length === 0) {
    console.log('No worker tasks found.\n')
    return
  }

  tasks.forEach(({ phase, subtask }) => {
    const statusIcon = getStatusIcon(subtask.status)
    console.log(`${statusIcon} ${phase.id}/${subtask.id}: ${subtask.name}`)
    console.log(`   Risk: ${subtask.risk} | Status: ${subtask.status}`)
    console.log(`   Context: ${subtask.context.substring(0, 80)}...`)
    console.log('')
  })
}

/**
 * Add a new phase
 */
export function addPhase(phase: Phase): boolean {
  const db = readTasks()

  // Check if phase ID already exists
  if (db.phases.some(p => p.id === phase.id)) {
    console.log(`Phase ID already exists: ${phase.id}`)
    return false
  }

  db.phases.push(phase)
  writeTasks(db)
  console.log(`✓ Added phase: ${phase.id}`)
  return true
}

/**
 * Add a subtask to an existing phase
 */
export function addSubtask(phaseId: string, subtask: Subtask): boolean {
  const db = readTasks()
  const phaseIndex = db.phases.findIndex(p => p.id === phaseId)

  if (phaseIndex === -1) {
    console.log(`Phase not found: ${phaseId}`)
    return false
  }

  // Check if subtask ID already exists in this phase
  if (db.phases[phaseIndex].subtasks.some(s => s.id === subtask.id)) {
    console.log(`Subtask ID already exists: ${subtask.id} in phase ${phaseId}`)
    return false
  }

  db.phases[phaseIndex].subtasks.push(subtask)
  writeTasks(db)
  console.log(`✓ Added subtask ${subtask.id} to phase ${phaseId}`)
  return true
}

/**
 * Helper: Get status icon
 */
function getStatusIcon(status: TaskStatus): string {
  switch (status) {
    case 'not_started':
      return '⚪'
    case 'in_progress':
      return '🔵'
    case 'blocked':
      return '🔴'
    case 'complete':
      return '✅'
    default:
      return '❓'
  }
}

/**
 * Display full task details
 */
export function showTaskDetails(phaseId: string, subtaskId?: string): void {
  const task = getTask(phaseId, subtaskId)

  if (!task) {
    return
  }

  if ('subtasks' in task) {
    // It's a phase
    const phase = task as Phase
    console.log(`\n📋 Phase: ${phase.name}`)
    console.log(`ID: ${phase.id}`)
    console.log(`Status: ${phase.status}`)
    console.log(`Created: ${phase.created}`)
    console.log(`\nOverview:\n${phase.overview}`)
    console.log(`\nSubtasks: ${phase.subtasks.length}`)
    phase.subtasks.forEach(st => {
      const icon = getStatusIcon(st.status)
      console.log(`  ${icon} ${st.id}. ${st.name}`)
    })
  } else {
    // It's a subtask
    const subtask = task as Subtask
    console.log(`\n🔧 Subtask: ${subtask.name}`)
    console.log(`ID: ${subtaskId}`)
    console.log(`Status: ${subtask.status}`)
    console.log(`Assignee: ${subtask.assignee}`)
    console.log(`Risk: ${subtask.risk}`)

    if (subtask.dependencies.length > 0) {
      console.log(`Dependencies: ${subtask.dependencies.join(', ')}`)
    }

    if (subtask.started_at) {
      console.log(`Started: ${subtask.started_at}`)
    }

    if (subtask.completed_at) {
      console.log(`Completed: ${subtask.completed_at}`)
    }

    if (subtask.files_modified.length > 0) {
      console.log(`\nFiles Modified:`)
      subtask.files_modified.forEach(file => {
        console.log(`  - ${file}`)
      })
    }

    console.log(`\nContext:\n${subtask.context}`)
    console.log(`\nPrompt:\n${subtask.prompt}`)
    console.log(`\nSuccess Criteria:`)
    subtask.success_criteria.forEach(criteria => {
      console.log(`  - ${criteria}`)
    })

    if (subtask.comments.length > 0) {
      console.log(`\nComments:`)
      subtask.comments.forEach(comment => {
        console.log(`  [${comment.timestamp}] ${comment.author}: ${comment.text}`)
      })
    }
  }

  console.log('\n')
}

/**
 * Helper: Get subtask from database
 */
function getSubtask(db: TasksDatabase, phaseId: string, subtaskId: string): Subtask | null {
  const phase = db.phases.find(p => p.id === phaseId)
  if (!phase) return null

  const subtask = phase.subtasks.find(s => s.id === subtaskId)
  return subtask || null
}

/**
 * HOOK: Get tasks ready to work on (dependencies satisfied)
 */
export function getReadyTasks(): Array<{
  phase: Phase
  subtask: Subtask
  canStart: boolean
  inProgress: boolean
}> {
  const db = readTasks()
  const ready: Array<{
    phase: Phase
    subtask: Subtask
    canStart: boolean
    inProgress: boolean
  }> = []

  for (const phase of db.phases) {
    for (const subtask of phase.subtasks) {
      // Skip if not assigned to worker
      if (subtask.assignee !== 'worker') continue

      // Skip if already complete
      if (subtask.status === 'complete') continue

      // Check dependencies
      const blockedBy = subtask.dependencies.filter(depId => {
        const dep = phase.subtasks.find(s => s.id === depId)
        return dep?.status !== 'complete'
      })

      if (blockedBy.length === 0) {
        ready.push({
          phase,
          subtask,
          canStart: subtask.status === 'not_started',
          inProgress: subtask.status === 'in_progress'
        })
      }
    }
  }

  return ready
}

/**
 * List ready tasks (with dependencies satisfied)
 */
export function listReadyTasks(): void {
  const ready = getReadyTasks()

  console.log('\n✅ Ready Tasks (dependencies satisfied)\n')

  if (ready.length === 0) {
    console.log('No ready tasks found.\n')
    return
  }

  ready.forEach(({ phase, subtask, canStart, inProgress }) => {
    const statusIcon = inProgress ? '🔵' : '⚪'
    const statusText = inProgress ? 'IN PROGRESS' : 'NOT STARTED'
    console.log(`${statusIcon} ${phase.id}/${subtask.id}: ${subtask.name}`)
    console.log(`   Status: ${statusText} | Risk: ${subtask.risk}`)
    console.log(`   Context: ${subtask.context.substring(0, 80)}...`)
    console.log('')
  })
}

/**
 * HOOK: Start a task
 * Checks dependencies, updates status to in_progress, records started_at
 */
export function startTask(phaseId: string, subtaskId: string): boolean {
  const db = readTasks()
  const phase = db.phases.find(p => p.id === phaseId)

  if (!phase) {
    console.log(`❌ Phase not found: ${phaseId}`)
    return false
  }

  const subtask = phase.subtasks.find(s => s.id === subtaskId)

  if (!subtask) {
    console.log(`❌ Subtask not found: ${subtaskId} in phase ${phaseId}`)
    return false
  }

  // Check if already started or completed
  if (subtask.status === 'in_progress') {
    console.log(`ℹ️  Task ${phaseId}/${subtaskId} is already in progress`)
    return true
  }

  if (subtask.status === 'complete') {
    console.log(`ℹ️  Task ${phaseId}/${subtaskId} is already complete`)
    return true
  }

  // Check dependencies
  const blockedBy = subtask.dependencies.filter(depId => {
    const dep = phase.subtasks.find(s => s.id === depId)
    return dep?.status !== 'complete'
  })

  if (blockedBy.length > 0) {
    console.log(`⚠️  Task ${phaseId}/${subtaskId} is blocked by: ${blockedBy.join(', ')}`)
    console.log(`   Complete these tasks first before starting this one.`)
    return false
  }

  // Update status and timestamp
  subtask.status = 'in_progress'
  subtask.started_at = new Date().toISOString()

  // Log start
  subtask.comments.push({
    timestamp: new Date().toISOString(),
    author: 'worker',
    text: 'Task started'
  })

  writeTasks(db)
  console.log(`✅ Started task ${phaseId}/${subtaskId}`)
  return true
}

/**
 * HOOK: Track file modification
 * Adds file to files_modified array if not already present
 */
export function trackFileModification(
  phaseId: string,
  subtaskId: string,
  filePath: string
): boolean {
  const db = readTasks()
  const subtask = getSubtask(db, phaseId, subtaskId)

  if (!subtask) {
    console.log(`❌ Task not found: ${phaseId}/${subtaskId}`)
    return false
  }

  if (!subtask.files_modified.includes(filePath)) {
    subtask.files_modified.push(filePath)
    writeTasks(db)
    console.log(`📝 Tracked file: ${filePath}`)
  }

  return true
}

/**
 * HOOK: Complete a task
 * Updates status to complete, records completed_at, adds comment
 */
export function completeTask(
  phaseId: string,
  subtaskId: string,
  completionNote?: string
): boolean {
  const db = readTasks()
  const subtask = getSubtask(db, phaseId, subtaskId)

  if (!subtask) {
    console.log(`❌ Task not found: ${phaseId}/${subtaskId}`)
    return false
  }

  // Update status and timestamp
  subtask.status = 'complete'
  subtask.completed_at = new Date().toISOString()

  // Log completion
  subtask.comments.push({
    timestamp: new Date().toISOString(),
    author: 'worker',
    text: completionNote || 'Task completed successfully'
  })

  writeTasks(db)
  console.log(`✅ Completed task ${phaseId}/${subtaskId}`)

  if (completionNote) {
    console.log(`   Note: ${completionNote}`)
  }

  return true
}

/**
 * HOOK: Block a task
 * Updates status to blocked, adds comment with reason
 */
export function blockTask(
  phaseId: string,
  subtaskId: string,
  reason: string
): boolean {
  const db = readTasks()
  const subtask = getSubtask(db, phaseId, subtaskId)

  if (!subtask) {
    console.log(`❌ Task not found: ${phaseId}/${subtaskId}`)
    return false
  }

  // Update status
  subtask.status = 'blocked'

  // Log blocker
  subtask.comments.push({
    timestamp: new Date().toISOString(),
    author: 'worker',
    text: `BLOCKED: ${reason}`
  })

  writeTasks(db)
  console.log(`🚫 Task ${phaseId}/${subtaskId} marked as blocked`)
  console.log(`   Reason: ${reason}`)

  return true
}

// CLI support - if run directly
if (require.main === module) {
  const args = process.argv.slice(2)
  const command = args[0]

  switch (command) {
    case 'list':
      listTasks()
      break
    case 'worker':
      listWorkerTasks()
      break
    case 'ready':
      listReadyTasks()
      break
    case 'show':
      showTaskDetails(args[1], args[2])
      break
    case 'status':
      updateTaskStatus(args[1], args[2] as TaskStatus, args[3])
      break
    case 'comment':
      addComment(args[1], args[2] as CommentAuthor, args[3], args[4])
      break
    case 'start':
      startTask(args[1], args[2])
      break
    case 'complete':
      completeTask(args[1], args[2], args[3])
      break
    case 'block':
      blockTask(args[1], args[2], args.slice(3).join(' '))
      break
    case 'file':
      trackFileModification(args[1], args[2], args[3])
      break
    default:
      console.log(`
Task Manager CLI

Usage:
  npx tsx task-manager.ts list                           - List all tasks
  npx tsx task-manager.ts worker                         - List worker tasks
  npx tsx task-manager.ts ready                          - List ready tasks (dependencies satisfied)
  npx tsx task-manager.ts show <phaseId> [subId]         - Show task details
  npx tsx task-manager.ts status <phaseId> <status> [subId] - Update status
  npx tsx task-manager.ts comment <phaseId> <author> <text> [subId] - Add comment

  Hook Commands:
  npx tsx task-manager.ts start <phaseId> <subId>        - Start task (checks dependencies)
  npx tsx task-manager.ts complete <phaseId> <subId> [note] - Complete task
  npx tsx task-manager.ts block <phaseId> <subId> <reason> - Block task
  npx tsx task-manager.ts file <phaseId> <subId> <path>  - Track file modification
      `)
  }
}
