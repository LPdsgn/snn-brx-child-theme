# AI Customizations — SNN Child Theme for Pompeii Alive

This document describes the customizations made to the SNN Bricks child theme AI system for the Discovery Pompeii Alive project.

---

## 1. Anthropic Provider Support

Native Anthropic (Claude) API support has been added as a first-class provider option alongside OpenRouter, OpenAI, and Custom.

### Files Modified

| File | Changes |
|---|---|
| `includes/ai/ai-settings.php` | Added "Anthropic" option to provider dropdown, registered `snn_anthropic_api_key` and `snn_anthropic_model` settings, added Anthropic settings panel (API key + model with datalist), updated JS toggle logic |
| `includes/ai/ai-api.php` | Added `anthropic` case (endpoint: `https://api.anthropic.com/v1/messages`, default model: `claude-sonnet-4-20250514`), added `provider` key to the returned config array |
| `includes/ai/ai-overlay.php` | Added `provider` to JS config, added `snnBuildAIRequest()` and `snnParseAIResponse()` helpers, updated both fetch calls (single + bulk) |
| `includes/ai/ai-block-editor.php` | Added `provider` to localized config, added same helpers, updated text generation fetch call |
| `includes/ai/ai-seo-generation.php` | Added `provider` to config, rewrote `callAI()` with Anthropic branch |
| `includes/ai/ai-agent-and-chat.php` | Updated `callAI()` with Anthropic branch for headers, body, and response parsing |
| `includes/ai/ai-agent-and-chat-bricks.php` | Same treatment for its `callAI()` function |

### Anthropic API Differences Handled

The Anthropic Messages API differs from the OpenAI-compatible format used by the other providers:

| Aspect | OpenAI / OpenRouter / Custom | Anthropic |
|---|---|---|
| Auth header | `Authorization: Bearer <key>` | `x-api-key: <key>` |
| Extra headers | — | `anthropic-version: 2023-06-01`, `anthropic-dangerous-direct-browser-access: true` |
| System prompt | `{ role: "system", content: "..." }` message | Top-level `system` field in request body |
| `max_tokens` | Optional | Required |
| Response body | `data.choices[0].message.content` | `data.content[0].text` |

### Settings UI

When "Anthropic" is selected as the API Provider in **AI Settings**, the panel shows:

- **Anthropic API Key** — password field, with link to `console.anthropic.com`
- **Anthropic Model** — text field with datalist suggesting `claude-opus-4-0-20250514`, `claude-sonnet-4-20250514`, `claude-haiku-4-20250414`, with link to Anthropic model docs

### Note on Image Generation

The image generation feature in the Block Editor uses OpenRouter-specific request formats (`modalities`, `image_config`). This is not supported by the Anthropic API and remains unchanged — image generation will not work when Anthropic is the selected provider.

---

## 2. System Prompts

The SNN AI system uses **two separate system prompts** for different contexts.

### 2.1 Content System Prompt (`snn_system_prompt`)

- **Where to configure:** WP Admin > **AI Settings** > System Prompt
- **WordPress option:** `snn_system_prompt`
- **Used by:**
  - `ai-overlay.php` — AI button in Bricks Builder fields (single + bulk editing)
  - `ai-block-editor.php` — AI panel in the Block Editor (Gutenberg)
  - `ai-seo-generation.php` — SEO title and description generation
- **Purpose:** Instructs the AI on how to create and edit content directly in text fields. Covers brand voice, tone of voice, formatting rules, and content guidelines.

The full prompt text is in `WEBSITE/ai-system-prompt.md` (first section).

### 2.2 Agent System Prompt (`snn_ai_agent_system_prompt`)

- **Where to configure:** WP Admin > **AI Agent Settings** > System Prompt
- **WordPress option:** `snn_ai_agent_system_prompt`
- **Used by:**
  - `ai-agent-and-chat.php` — AI chat overlay in wp-admin
  - `ai-agent-and-chat-bricks.php` — AI chat overlay inside Bricks Builder
- **Purpose:** Instructs the AI as an operational WordPress agent that can execute abilities (create posts, analyze SEO, manage taxonomies, edit block content, etc.)
- **Important:** At line 1087 of `ai-agent-and-chat.php`, this prompt **overrides** the `systemPrompt` from the global config: `$ai_config['systemPrompt'] = $this->get_system_prompt();`
- **Note:** The code automatically appends ~3,000 tokens of operational instructions after this base prompt (page context, abilities list, execution rules, block generation rules). Keep the base prompt concise.

The full prompt text is in `WEBSITE/ai-system-prompt.md` (second section).

### Data Flow Summary

```
ai-api.php → snn_get_ai_api_config()
  ├── systemPrompt = get_option('snn_system_prompt')
  ├── provider, apiKey, model, apiEndpoint, ...
  │
  ├──→ ai-overlay.php         (uses systemPrompt as-is)
  ├──→ ai-block-editor.php    (uses systemPrompt as-is)
  ├──→ ai-seo-generation.php  (uses systemPrompt as-is)
  │
  ├──→ ai-agent-and-chat.php
  │     └── OVERRIDES systemPrompt with get_option('snn_ai_agent_system_prompt')
  │         then appends: page context + abilities + execution rules
  │
  └──→ ai-agent-and-chat-bricks.php
        └── OVERRIDES systemPrompt with get_option('snn_ai_agent_system_prompt')
            then appends: page context + abilities + execution rules
```

### Quick Reference Table

| Feature | System Prompt Used | Config Source |
|---|---|---|
| AI button in Bricks fields | `snn_system_prompt` | AI Settings |
| AI panel in Block Editor | `snn_system_prompt` | AI Settings |
| SEO generation | `snn_system_prompt` | AI Settings |
| Chat AI in wp-admin | `snn_ai_agent_system_prompt` | AI Agent Settings |
| Chat AI in Bricks Builder | `snn_ai_agent_system_prompt` | AI Agent Settings |
