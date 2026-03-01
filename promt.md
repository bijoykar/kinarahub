# Prompt Log

All prompts given in this project, in order.

---

## 1
retrive spec.md

---

## 2
create spec.md "kinara store inventory management and sales management

Create KINARA store hub , where multiple kinara store can register with mail and mobile
backend - PHP, mysql,
frontend - php with html, css javascript
It should have api module , so that it can support android and ios app build
For now all kinara will share same database tables, but keep in mind we can setup total new database for each kinara store
Shop owner can do inventory crud operation  and it show with diffiernet color and alert abount low inventory and out of staock products
They can upload inventory with csv as bulk data upload
Shop owner can maintain sales , if they dont want to provide customer info it will use default customer info to track it, So that near future it can extends with real customer
A modern dashboard will be provided , where he can see inventory stock and sales in day, week and month, year wise
they can see differnet reports as well , which will help them maintain inventory and also will help to generate tax  report

## Suggested Improvements Beyond Original Brief

1. Pagination — Add Spring Data Pageable support for large inventories (100+ items)
2. Audit Fields — createdAt/updatedAt on all entities for traceability
3. Optimistic Locking — @Version field to prevent concurrent edit conflicts
4. Toast Notifications — User feedback for all CRUD actions (success/error)
5. Keyboard Shortcuts — Escape to close modals, Enter to submit, Ctrl+N for new item
6. Export to CSV — Download inventory as CSV file for offline use
7. Dark Mode — Toggle between light/dark themes using CSS variables
8. Form Auto-focus — Focus first field when modal opens for faster data entry
9. Undo Delete — Brief "Undo" toast after deletion before permanent removal
10. Input Sanitization — Trim whitespace, normalize SKU to uppercase on backend"

---

## 3
read this @spec.md and interview me in detail using the AskUserQuestionTool about literally anything : technical implementation, UI & UX, concerns, tradeoffs etc. but make sure question are not obvious . be very in-depth and continue interviewing me continually until it's complete, then write the spec to the file

---

## 4
create a skill with "---
name: frontend-design
description: Create distinctive, production-grade frontend interfaces with high design quality. Use this skill when the user asks to build web components, pages, artifacts, posters, or applications (examples include websites, landing pages, dashboards, React components, HTML/CSS layouts, or when styling/beautifying any web UI). Generates creative, polished code and UI design that avoids generic AI aesthetics.
license: Complete terms in LICENSE.txt
---
[full skill content]"

---

## 5
create Backend Agent:
Purpose: Handles server-side logic, API development, and integration.
Tools: bash (for running scripts), file system operations (read/write), custom tools for specific frameworks (e.g., Node.js, Django).
System Prompt: Focus on efficiency, security best practices, API conventions, and integration patterns.

---

## 6
/init
Please analyze this codebase and create a CLAUDE.md file

---

## 7
create Frontend Agent:
Purpose: Works on user interfaces, UI/UX design implementation, and browser compatibility.
Tools: File system operations, bash (for running local dev servers or build commands).
System Prompt: Emphasize design principles, accessibility (a11y) standards, framework conventions, and cross-browser testing knowledge.

---

## 8
Create Database Agent:
Purpose: Manages database schemas, queries, optimization, and data integrity.
Tools: Custom tools for database interactions (e.g., SQL execution via a script), bash, file system operations.
System Prompt: Instruct the agent to prioritize data integrity, security, and performance when writing or reviewing database-related code.

---

## 9
Create Android Agent:
Purpose: Develops, tests, and debugs native Android applications.
Tools: adb (Android Debug Bridge) using a Model Context Protocol (MCP) server, file operations, bash.
System Prompt: Focus on Android development best practices, Kotlin/Java knowledge, UI guidelines, and debugging using specific tools.

---

## 10
Create iOS Agent:
Purpose: Develops, tests, and debugs native iOS applications.
Tools: Tools for interacting with the iOS simulator, bash, file operations (potentially using a framework like agent-device).
System Prompt: Guide the agent on Swift/Objective-C best practices, iOS Human Interface Guidelines, and Apple's development ecosystem.

---

## 11
Create an agent team to build a full-stack mobile app. Include a backend teammate for APIs, a frontend teammate for web, a database teammate for the schema, and specialized teammates for Android and iOS.

---

## 12
create planing in phase wise

---

## 13
start with phase 1 and complete all

---

## 14
start with remaining phase run all agest parallaly
