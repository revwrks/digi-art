# Vercel React Best Practices
Source: vercel-labs/agent-skills, skill "vercel-react-best-practices" (MIT) — https://github.com/vercel-labs/agent-skills
Paste this file into a chat or Project when writing, reviewing, or refactoring React/Next.js code.

---

Comprehensive performance optimization guide for React and Next.js applications, maintained by Vercel. Reference these rules when: writing new React components or Next.js pages, implementing data fetching (client or server-side), reviewing code for performance issues, refactoring existing React/Next.js code, or optimizing bundle size/load times.

## Rule categories, by priority

| Priority | Category | Impact | Prefix |
|---|---|---|---|
| 1 | Eliminating Waterfalls | CRITICAL | `async-` |
| 2 | Bundle Size Optimization | CRITICAL | `bundle-` |
| 3 | Server-Side Performance | HIGH | `server-` |
| 4 | Client-Side Data Fetching | MEDIUM-HIGH | `client-` |
| 5 | Re-render Optimization | MEDIUM | `rerender-` |
| 6 | Rendering Performance | MEDIUM | `rendering-` |
| 7 | JavaScript Performance | LOW-MEDIUM | `js-` |
| 8 | Advanced Patterns | LOW | `advanced-` |

## 1. Eliminating Waterfalls (CRITICAL)
- Check cheap sync conditions before awaiting flags or remote values
- Move `await` into the branches where it's actually used
- Use `Promise.all()` for independent operations
- Use a partial-dependency helper (e.g. `better-all`) when only some promises depend on each other
- In API routes: start promises early, await late
- Use Suspense to stream content instead of blocking on everything

## 2. Bundle Size Optimization (CRITICAL)
- Import directly; avoid barrel files
- Prefer statically analyzable import/file-system paths (avoids broad bundles and traces)
- Use `next/dynamic` for heavy components
- Load analytics/logging after hydration
- Load modules only when the feature is actually activated
- Preload on hover/focus for perceived speed

## 3. Server-Side Performance (HIGH)
- Authenticate server actions like API routes
- Use `React.cache()` for per-request deduplication
- Use an LRU cache for cross-request caching
- Avoid duplicate serialization in RSC props
- Hoist static I/O (fonts, logos) to module level
- Avoid module-level mutable request state in RSC/SSR
- Minimize data passed to client components
- Restructure components to parallelize fetches
- Chain nested fetches per item inside `Promise.all`, not sequentially
- Use `after()` for non-blocking operations

## 4. Client-Side Data Fetching (MEDIUM-HIGH)
- Use SWR (or similar) for automatic request deduplication
- Deduplicate global event listeners
- Use passive listeners for scroll
- Version and minimize localStorage data

## 5. Re-render Optimization (MEDIUM)
- Don't subscribe to state that's only used in callbacks
- Extract expensive work into memoized components
- Hoist default non-primitive props instead of inlining new objects
- Use primitive dependencies in effects
- Subscribe to derived booleans, not raw values
- Derive state during render, not in effects
- Use functional `setState` for stable callbacks
- Pass a function to `useState` for expensive initial values
- Avoid `useMemo` for simple primitive expressions
- Split hooks that have independent dependencies
- Put interaction logic in event handlers, not effects
- Use `startTransition` for non-urgent updates
- Use `useDeferredValue` to keep input responsive during expensive renders
- Use refs for transient, frequently-changing values
- Don't define components inside components

## 6. Rendering Performance (MEDIUM)
- Animate a wrapping `<div>`, not the SVG element itself
- Use `content-visibility` for long lists
- Extract static JSX outside components
- Reduce SVG coordinate precision
- Use an inline script for client-only data to avoid hydration flicker
- Suppress only genuinely expected hydration mismatches
- Use the `Activity` component (or equivalent) for show/hide instead of unmount
- Use a ternary for conditional rendering, not `&&`
- Prefer `useTransition` for loading state
- Use React DOM resource hints for preloading
- Use `defer` or `async` on script tags

## 7. JavaScript Performance (LOW-MEDIUM)
- Group CSS changes via classes or `cssText` instead of one-by-one
- Build a `Map` for repeated lookups
- Cache object property access in loops
- Cache function results in a module-level `Map`
- Cache localStorage/sessionStorage reads
- Combine multiple `filter`/`map` calls into one loop
- Check array length before an expensive comparison
- Return early from functions
- Hoist `RegExp` creation outside loops
- Use a loop for min/max instead of sorting
- Use `Set`/`Map` for O(1) lookups
- Use `toSorted()` for immutability
- Use `flatMap` to map and filter in one pass
- Defer non-critical work to browser idle time (`requestIdleCallback`)

## 8. Advanced Patterns (LOW)
- Don't put `useEffectEvent` results in effect dependency arrays
- Store event handlers in refs
- Initialize app-level state once per app load
- Use a `useLatest`-style hook for stable callback refs

## Note
This is the categorized quick-reference. The full guide (all 70 rules with incorrect/correct code examples) lives in `AGENTS.md` in the source repo — link above — if you want the expanded version with examples for a specific rule.
