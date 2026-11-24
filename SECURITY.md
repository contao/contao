# Security Policy

The general Contao security policy is available here: https://github.com/contao/contao/security/policy

## Supported versions

Check the [open milestones][1] for supported versions. If the affected version is no longer supported, do not report
vulnerabilities as they will not be fixed. If a vulnerability is found in the `main` branch (unreleased), please report
it as a regular GitHub issue.

## Threat model

- **Trusted:** Administrators have full control over the installation.
- **Untrusted:** Restricted backend users, frontend users and visitors.
- **Security boundaries:** Restricted users must not be able to gain administrator privileges, bypass field-level
  permissions, escape their filemounts/pagemounts, or act on records outside their access.
- **Implicit admins:** Restricted backend users with access to the template editor, the Template Studio, the unfiltered
  HTML element or the "Listing" frontend module, as well as users with permission to edit page layouts or import themes
  are considered implicit administrators.
- **Out of scope:** Server hardening, compromised hosting, malicious third-party extensions.

A finding is only a vulnerability if it crosses one of these boundaries.

## Non-issues

The following behaviors are intended by design or are not vulnerabilities. Do not report them. If a finding only
partially matches a non-issue (different preconditions, different impact, or a different surface), report it and state
explicitly how it differs, referencing the non-issue ID.

### NI-1: Restoring earlier values via version history

- **Statement:** A backend user who is allowed to edit a record can restore any earlier version of that record from the
  version history. This writes all versioned fields of the snapshot back to the record, including fields the user is not
  permitted to edit directly (field-level restrictions, `alexf`), e.g. `published`, `start`, `stop` or page
  access-control fields.
- **Rationale:** The user cannot write arbitrary values. Only values that were previously stored in a snapshot of the
  same record can be restored, and only for records the user can already edit. The impact is limited to rolling a
  record back to a state it already had.
- **Applies when:** The user has edit access to the record, and restoring does not give them administrator or
  implicit-administrator privileges.
- **Does not apply when:** Restoring gives a restricted user administrator privileges (`tl_user.admin`), implicit-admin
  permissions (see threat model), or membership in a group holding them.

[1]: https://api.github.com/repos/contao/contao/milestones?state=open
