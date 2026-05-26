# ADR-0003: No CAPTCHA anywhere

**Status:** Accepted
**Date:** 2026-05-26

## Context

The initial design included Cloudflare Turnstile CAPTCHA for anonymous uploads (API and landing page). This adds complexity: SDK integration, contract/driver pattern, frontend widget, token verification.

## Decision

No CAPTCHA in the project — neither API nor landing page. Protection against abuse relies solely on rate limiting per IP (10/min anonymous, 60/min authenticated) plus the IP blocking system in the security module.

## Consequences

- The `Captcha/` directory, `CaptchaVerifier` contract, and all drivers (Turnstile, reCAPTCHA, Null) are removed from the security module scope.
- API uploads are a simple `curl` with no friction.
- Landing page form has no CAPTCHA widget.
- If abuse becomes a problem, rate limits can be tightened or IP blocking can be made more aggressive before reconsidering CAPTCHA.
