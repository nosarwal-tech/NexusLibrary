# Library Management System - Specification

## Concept & Vision

A futuristic, glassmorphic Library Management System that feels like a premium control center for library operations. The interface combines sleek dark aesthetics with smooth animations, making library management feel modern and sophisticated. Designed for librarians who deserve tools as beautiful as the books they curate.

## Design Language

### Aesthetic Direction
- **Theme**: Futuristic Glassmorphism Dark Mode
- **Inspiration**: Sci-fi command centers meets premium fintech dashboards
- **Glass Effect**: Semi-transparent panels with backdrop blur, subtle borders, and soft glows

### Color Palette
- **Primary**: `#6C5CE7` (Electric Purple)
- **Secondary**: `#00D9FF` (Cyan Glow)
- **Accent**: `#FF6B9D` (Neon Pink)
- **Success**: `#00E676` (Matrix Green)
- **Warning**: `#FFD93D` (Amber)
- **Danger**: `#FF4757` (Coral Red)
- **Background**: `#0a0a1a` (Deep Space)
- **Surface**: `rgba(255, 255, 255, 0.05)` (Glass panels)
- **Text Primary**: `#FFFFFF`
- **Text Secondary**: `#8892B0`

### Typography
- **Headings**: 'Orbitron', sans-serif (futuristic feel)
- **Body**: 'Inter', sans-serif (clean readability)
- **Monospace**: 'JetBrains Mono' (for IDs, codes)

### Spatial System
- Base unit: 8px
- Card padding: 24px
- Section gaps: 32px
- Border radius: 16px (cards), 8px (buttons), 24px (modals)

### Motion Philosophy
- **Page transitions**: Fade-in with subtle upward slide (300ms ease-out)
- **Cards**: Scale 1.02 on hover with glow intensification
- **Buttons**: Background color shift with subtle lift
- **Sidebar**: Smooth slide with icon bounce on active
- **Data loading**: Skeleton shimmer effect
- **Notifications**: Slide-in from right with progress bar

### Visual Assets
- **Icons**: Lucide Icons (clean, consistent)
- **Charts**: Custom SVG with gradient fills
- **Decorative**: Subtle grid pattern background, floating orbs

## Layout & Structure

### Page Architecture
1. **Sidebar** (fixed, collapsible)
   - Logo with glow effect
   - Navigation links with active indicators
   - User profile mini-card
   - Collapse toggle

2. **Main Content Area**
   - Top bar with breadcrumbs, search, notifications
   - Page title with stats summary
   - Content grid (responsive)
   - Footer with quick links

3. **Dashboard Layout**
   - Stats cards row (4-6 cards)
   - Charts section (2 columns)
   - Recent activity feed
   - Quick actions panel

### Responsive Strategy
- Desktop: Full sidebar + expanded content
- Tablet: Collapsed sidebar + full content
- Mobile: Bottom navigation + stacked content

## Features & Interactions

### Authentication
- **Login**: Email + password with remember me
- **Roles**: Admin, Librarian, Member
- **Session**: 24-hour timeout with warning modal
- **Logout**: Confirmation + redirect

### Dashboard
- **Stats Cards**: Animated counters, click to filter
- **Charts**: Line (overdue trend), Bar (monthly issues), Pie (category distribution)
- **Activity Feed**: Real-time updates via AJAX
- **Quick Actions**: Issue, Return, Search shortcuts

### Book Management
- **CRUD**: Full add/edit/delete with AJAX
- **Search**: Instant search with debouncing
- **Filters**: Category, availability, type
- **Bulk Actions**: Select multiple, batch update
- **QR Code**: Generate on add, display on view

### Transactions
- **Issue Flow**: Select book → Select member → Set due date → Confirm
- **Return Flow**: Scan/select → Calculate fine → Process return
- **Renew**: Extend due date, track renewals
- **Fine Calculation**: 7 days grace, then $0.50/day

### User Management
- **Registration**: Form validation, password strength
- **Roles**: Admin (full), Librarian (limited), Member (self-service)
- **Limits**: Configurable borrow limits per role

### Reports
- **Daily**: Today's activity snapshot
- **Monthly**: 30-day trends
- **Custom Range**: Date picker for specific periods
- **Export**: CSV download capability

## Component Inventory

### Cards
- **Stats Card**: Icon, label, value, trend indicator
- **Book Card**: Cover, title, author, availability badge
- **User Card**: Avatar, name, role badge, status
- **Transaction Card**: Book, user, dates, fine status

### Buttons
- **Primary**: Gradient purple, hover glow
- **Secondary**: Ghost style, border on hover
- **Danger**: Red with shake animation on click
- **Icon Button**: Circular, tooltip on hover

### Forms
- **Input**: Glass background, focus glow, floating label
- **Select**: Custom dropdown with search
- **Date Picker**: Calendar popup, disabled past dates
- **Checkbox**: Custom styled with check animation

### Tables
- **Header**: Sticky, sortable columns
- **Rows**: Hover highlight, action buttons
- **Pagination**: Page numbers, prev/next, items per page
- **Empty State**: Illustration with message

### Modals
- **Overlay**: Blur background
- **Panel**: Glass effect, close button
- **Confirmation**: Two-button layout
- **Form Modal**: Scrollable content area

### Notifications
- **Toast**: Top-right, auto-dismiss
- **Badge**: Notification count
- **Alert Banner**: Full-width, dismissible

## Technical Approach

### Architecture
- **Pattern**: MVC-inspired (procedural PHP)
- **Database**: MySQL with prepared statements
- **Frontend**: Vanilla JS + AJAX
- **Styling**: Custom CSS with CSS Variables

### Database Schema
```
Tables:
- users (id, name, email, password, role, phone, address, created_at, status)
- books (id, isbn, title, author, publisher, category, type, description, shelf, copies, available, created_at)
- transactions (id, book_id, user_id, issue_date, due_date, return_date, fine, status, renewed)
- categories (id, name, description)
- fines (id, transaction_id, amount, paid, created_at)
- notifications (id, user_id, message, type, read, created_at)
- settings (id, key, value)
```

### API Design
- **Authentication**: POST /auth/login, POST /auth/logout
- **Books**: GET/POST/PUT/DELETE /api/books.php
- **Users**: GET/POST/PUT/DELETE /api/users.php
- **Transactions**: GET/POST /api/transactions.php
- **Reports**: GET /api/reports.php

### Security
- Prepared statements for all queries
- Password hashing with bcrypt
- Session regeneration on login
- CSRF tokens for forms
- XSS prevention with htmlspecialchars
