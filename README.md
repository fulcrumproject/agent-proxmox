# Agent Smith

A PHP-based automation service that manages virtual machines in Proxmox environments through a job queue system.

## Overview

Agent Smith acts as a bridge between FulcrumCore and Proxmox VE (Virtual Environment), creating a seamless infrastructure management system. It handles common VM operations like:

- Creating new virtual machines
- Deleting existing VMs
- Starting/Stopping VMs
- Updating VM configurations
- Monitoring VM metrics

## Infrastructure Flow

1. **FulcrumCore**

   - Central orchestration service
   - Initiates VM management requests
   - Tracks service states and requirements
   - Communicates with Agent Smith via API

2. **Agent Smith**

   - Receives requests from FulcrumCore
   - Manages job queue and execution
   - Translates service requests to Proxmox operations
   - Maintains VM-to-Service mappings
   - Reports operation results back to FulcrumCore

3. **Proxmox VE**
   - Hosts the virtual machines
   - Executes VM operations
   - Provides real-time metrics
   - Manages physical resources

## Job Queue System

### Queue Processing

- Jobs stored in SQLite database
- Processed by priority and creation time
- Each job has unique ID and status tracking

### Queue.php (Main Queue Handler)

- Implements a singleton pattern for queue management
- Provides the main worker loop that runs continuously
- Manages job processing and execution flow
- Features:
  - Auto-restart capabilities based on resource usage
  - Job prioritization system
  - Error handling and recovery
  - Push mechanism for adding new jobs
  - Sleep cycles to prevent CPU overuse

### QueueMonitor.php (Queue Health Monitor)

- Monitors queue health and performance
- Manages critical system jobs
- Key responsibilities:
  - Tracks memory usage
  - Monitors execution time
  - Counts processed jobs
  - Ensures critical jobs exist:
    - Heartbeat checks
    - Pending job retrieval
    - Completed job pruning
    - Metric reporting
  - Implements automatic queue restart when:
    - Maximum jobs limit reached
    - Memory threshold exceeded
    - Time limit surpassed

### Heartbeat System

Agent Smith maintains an active connection with FulcrumCore through a heartbeat mechanism. This critical system component:

- Regularly updates Agent's status in FulcrumCore

### Connection Management

If Agent Smith fails to send heartbeats:

- FulcrumCore marks the agent as inactive after timeout

### Critical Job System

The queue maintains several critical jobs that ensure system health:

- `Heartbeat`: System alive checks
- `GetPendingJobs`: Retrieves new jobs from FulcrumCore
- `PruneCompletedJobs`: Cleans up finished jobs
- `ReportMetric`: Sends system metrics

### Job States

- `pending`: Awaiting processing
- `processing`: Under execution
- `completed`: Successfully finished
- `failed`: Error occurred

### Job Types

- `create`: Provisions new VMs with specified resources
- `delete`: Removes existing VMs
- `update`: Modifies VM configurations
- `start/stop`: Controls VM power state

## Database Schema

The service maintains several SQLite tables:

### Jobs Table

- Stores operation queue
- Tracks job status and history
- Contains operation parameters

### Service Mappings

- Links FulcrumCore services to Proxmox VMs
- Maintains relationship history
- Enables service tracking

## Requirements

- PHP 8.1+
- SQLite3
- Proxmox VE access credentials
- Network access to FulcrumCore API
- Network access to Proxmox API

## Security

Agent Smith requires:

- Proxmox API credentials
- FulcrumCore API authentication
- Secure network communication between components
