# Matt Pocock's Engineering Skills
Source: mattpocock/skills (MIT) — https://github.com/mattpocock/skills
Four of his skills, each usable on its own. Paste the whole file, or just the section you need, into a chat or Project.

---

## 1. Codebase Design ("Grill Me" companion — design deep modules)

Design **deep modules**: a lot of behaviour behind a small interface, placed at a clean seam, testable through that interface. Use this language and these principles wherever code is being designed or restructured. The aim is leverage for callers, locality for maintainers, and testability for everyone.

### Glossary
Use these terms exactly — don't substitute "component," "service," "API," or "boundary."

- **Module** — anything with an interface and an implementation. Deliberately scale-agnostic: a function, class, package, or tier-spanning slice.
- **Interface** — everything a caller must know to use the module correctly: type signature, invariants, ordering constraints, error modes, required configuration, performance characteristics.
- **Implementation** — what's inside a module. Distinct from **Adapter**: a thing can be a small adapter with a large implementation (a Postgres repo) or a large adapter with a small implementation (an in-memory fake).
- **Depth** — leverage at the interface: how much behaviour a caller/test can exercise per unit of interface they learn. **Deep** = large behaviour behind a small interface. **Shallow** = interface nearly as complex as the implementation.
- **Seam** *(Michael Feathers)* — a place where you can alter behaviour without editing in that place; where a module's interface lives.
- **Adapter** — a concrete thing that satisfies an interface at a seam.
- **Leverage** — what callers get from depth: more capability per unit of interface learned.
- **Locality** — what maintainers get from depth: change, bugs, knowledge, and verification concentrate in one place.

### Principles
- Depth is a property of the interface, not the implementation. A deep module can have internal seams (private, used by its own tests) as well as its external seam.
- **The deletion test**: imagine deleting the module. If complexity vanishes, it was a pass-through. If it reappears across N callers, it was earning its keep.
- **The interface is the test surface.** If you want to test past the interface, the module is probably the wrong shape.
- **One adapter means a hypothetical seam. Two adapters means a real one.** Don't introduce a seam unless something actually varies across it.

### Designing for testability
1. Accept dependencies, don't create them (pass in `paymentGateway`, don't `new StripeGateway()` inside).
2. Return results, don't produce side effects (`calculateDiscount(cart): Discount`, not `applyDiscount(cart): void`).
3. Small surface area — fewer methods and params means fewer, simpler tests.

### Rejected framings
- Depth as ratio of implementation-lines to interface-lines (Ousterhout) — rewards padding the implementation.
- "Interface" as just the TypeScript `interface` keyword — too narrow.
- "Boundary" — overloaded with DDD's bounded context. Say **seam** or **interface**.

---

## 2. Domain Modeling (build and sharpen a project's shared vocabulary)

Actively build and sharpen a project's domain model as you design — challenging terms, inventing edge-case scenarios, and writing the glossary and decisions down the moment they crystallise. (Merely reading an existing glossary for vocabulary is not this — that's a one-line habit any skill can do. This is for when you're *changing* the model.)

### File structure
Most repos have a single context:
```
/
├── CONTEXT.md
├── docs/adr/ (0001-*.md, 0002-*.md, ...)
└── src/
```
If a `CONTEXT-MAP.md` exists at the root, the repo has multiple contexts, each with its own `CONTEXT.md` and `docs/adr/`. Create these files lazily — only when you have something to write.

### During the session
- **Challenge against the glossary**: when a term conflicts with existing vocabulary, call it out immediately. "Your glossary defines 'cancellation' as X, but you seem to mean Y — which is it?"
- **Sharpen fuzzy language**: propose a precise canonical term for vague/overloaded words. "You're saying 'account' — do you mean the Customer or the User?"
- **Discuss concrete scenarios**: stress-test relationships with specific edge cases.
- **Cross-reference with code**: if stated behaviour contradicts the code, surface it.
- **Update the glossary inline**, the moment a term is resolved — don't batch it up. The glossary should be totally devoid of implementation details; it's a glossary, not a spec or scratchpad.
- **Offer an ADR only when all three are true**: (1) hard to reverse, (2) surprising without context, (3) the result of a real trade-off with genuine alternatives. Skip it if any is missing.

---

## 3. Grilling (the interview behind "Grill Me" — stress-test a plan before building)

Interview the user relentlessly about a plan, decision, or idea until every branch of the design tree is resolved.

Map the decision as a **design tree**: every decision branches into the decisions that hang off it. Work it in **rounds**. The **frontier** is every decision whose prerequisites are already settled — the questions you can ask *now* without guessing at unheard answers. Ask the whole frontier in one round, numbered, each with your recommended answer:

```
❓ Q1 - <question title>: <question body, may be multiple paragraphs / multiple choice>
➡️ <your recommended answer>
```

Each round's answers reshape the tree — settled decisions unblock questions that depended on them. Recompute the frontier and ask the next round. A question whose answer depends on another still-open question belongs to a *later* round.

Finding **facts** is your job, never the user's — dispatch a sub-agent to look things up rather than asking the user for anything you could find yourself. Don't block the rest of the frontier on a running lookup. The **decisions** are the user's — put each to them and wait.

Done when the frontier is empty: every branch visited, nothing silently assumed. Don't act on the plan until the user confirms shared understanding.

---

## 4. Diagnosing Bugs (a disciplined loop for hard bugs and perf regressions)

A discipline for hard bugs. Skip phases only when explicitly justified. Read any project glossary/decision docs first for context on the area you're touching.

### Phase 1 — Build a feedback loop (this is the skill; everything else is mechanical)
If you have a **tight** pass/fail signal that goes red on *this* bug, you will find the cause. If you don't, no amount of staring at code will save you. Spend disproportionate effort here.

Ways to construct one, roughly in this order: a failing test at the right seam; a curl/HTTP script against a dev server; a CLI invocation diffed against a known-good snapshot; a headless-browser script; replaying a captured trace; a throwaway harness; a property/fuzz loop; a bisection harness (`git bisect run`); a differential loop (old vs new); or, as a last resort, a human-in-the-loop script.

Then **tighten** the loop: faster, sharper signal, more deterministic (pin time, seed RNG, isolate filesystem/network). A 2-second deterministic loop is a debugging superpower; a 30-second flaky one barely helps.

For non-deterministic bugs: aim for a higher reproduction rate, not a clean repro — loop the trigger 100×, parallelise, add stress, narrow timing windows.

If you genuinely cannot build a loop: stop, say so, list what you tried, and ask for environment access, a captured artifact (HAR/log/core dump/recording), or permission to add temporary production instrumentation. Do not proceed to hypothesise without a loop.

**Completion criterion**: one command you've already run at least once, that is red-capable (catches this specific bug), deterministic, fast, and agent-runnable.

### Phase 2 — Reproduce + minimise
Confirm the loop reproduces the *user's* described failure, not a different nearby one. Then shrink to the smallest scenario that still goes red, cutting one variable at a time. Done when every remaining element is load-bearing.

### Phase 3 — Hypothesise
Generate 3–5 ranked, falsifiable hypotheses before testing any of them ("If X is the cause, then Y will make the bug disappear"). Show the ranked list to the user before testing — they often have domain knowledge that re-ranks it instantly.

### Phase 4 — Instrument
Each probe maps to a specific hypothesis; change one variable at a time. Prefer a debugger/REPL over logs; if logging, tag every line with a unique prefix (e.g. `[DEBUG-a4f2]`) so cleanup is one grep. For perf regressions, measure with a baseline (timing harness, profiler) and bisect — don't guess from logs.

### Phase 5 — Fix + regression test
Write the regression test *before* the fix, but only at a correct seam (one that exercises the real bug pattern as it occurs at the call site). If no correct seam exists, that itself is the finding — note it and flag it for an architecture pass. Otherwise: fail → fix → pass → re-run the original Phase 1 loop.

### Phase 6 — Cleanup + post-mortem
Confirm the original repro no longer reproduces, the regression test passes, all `[DEBUG-...]` instrumentation and throwaway prototypes are removed, and the correct hypothesis is stated in the commit/PR message. Then ask: what would have prevented this bug? If the answer is architectural, flag it as a follow-up — after the fix is in, not before.
