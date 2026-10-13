# Sending leads to CaRMen

Website forms and other systems can create leads with an API key. An admin creates keys
under **Admin → API Keys**; the full key (`crm_…`) is shown once, so store it where your form
backend keeps secrets. Use one key per website or system, so each can be revoked on its own.

A key can only create leads. It can't read or change anything else in the CRM.

## Request

```
POST https://crm.example.com/api/leads
X-Api-Key: crm_xxxxxxxx_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Content-Type: application/json

{
  "firstName": "Ada",
  "lastName":  "Lovelace",
  "email":     "ada@example.com",
  "phone":     "+45 12 34 56 78",
  "company":   "Analytical Engines",
  "jobTitle":  "Mathematician",
  "message":   "Please call me about pricing.",
  "source":    "Website contact form"
}
```

| Field | | |
|---|---|---|
| `email` | required | |
| `firstName`, `lastName` | required | or a single `name`, split on the last space |
| `phone` | optional | max 20 characters |
| `company` | optional | linked to the account with that name (case-insensitive), or a new account |
| `jobTitle` | optional | |
| `message` | optional | max 5000 characters, stored as a note |
| `source` | optional | where the lead came from, shown in the note |

Unknown fields are ignored.

## What happens

- **New email address** → a contact with status `lead`, plus a note with the message and source.
- **Known email address** → the contact is **not** changed (a form never overwrites CRM data);
  the submission, including any name/phone/company it contained, is added as a note.

## Responses

| Status | |
|---|---|
| `201` | `{"contactId": "…", "created": true}`, new contact |
| `200` | `{"contactId": "…", "created": false}`, existing contact, note added |
| `401` | missing, invalid or revoked key |
| `422` | `{"errors": {"field": "message"}}`, invalid input |
| `429` | more than 60 requests per minute for this key; see `Retry-After` |

Send leads from your **server**, not from browser JavaScript: anything in the browser is
visible to visitors, including the key.
