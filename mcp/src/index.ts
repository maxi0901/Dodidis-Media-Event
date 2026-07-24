#!/usr/bin/env node
/**
 * MCP-Server für das Dodidis-Media Agenturtool.
 *
 * Handelt via persönlichem API-Token (ENV DODIDIS_API_TOKEN) als der jeweilige
 * Nutzer — mit dessen Rollen/Rechten. Deckt Lesen (Fragen beantworten) und
 * Schreiben (Videos/Projekte eintragen, Status, Termine, Posting) ab. Die
 * Video-DATEI selbst wird bewusst NICHT hochgeladen (bleibt Browser→NAS).
 *
 * Ein generisches Fallback-Tool (tool_api_call) erreicht jeden erlaubten
 * Endpunkt, damit „alles, was der Account im Tool kann" abgedeckt ist.
 */
import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { z } from "zod";
import { apiCall } from "./client.js";

const server = new McpServer({ name: "dodidis-media", version: "0.1.0" });

function ok(data: unknown) {
  return { content: [{ type: "text" as const, text: JSON.stringify(data, null, 2) }] };
}
function fail(e: unknown) {
  return {
    content: [{ type: "text" as const, text: "Fehler: " + (e instanceof Error ? e.message : String(e)) }],
    isError: true,
  };
}

// Muss zu den vom Backend akzeptierten Status passen (build_project_params /
// projects.status-ENUM). 'skript' ist der Legacy-Default; 'gepostet' erfordert
// die Posting-Erweiterung (PR #300) — bis die deployed ist, weist das Backend
// 'gepostet' mit 400 ab (wird sauber als Fehler zurückgegeben).
const STATUS = [
  "idee", "skript", "geplant", "gedreht", "schnitt", "korrektur", "fertig", "freigegeben", "gepostet", "archiviert",
] as const;

// Optionale Projektfelder (camelCase — exakt wie die API sie erwartet).
const projektFelder = {
  customerId: z.string().optional().describe("Kunden-ID (siehe kunden_auflisten)"),
  videografId: z.string().optional().describe("User-ID des Videografen (siehe mitarbeiter_auflisten)"),
  cutterId: z.string().optional().describe("User-ID des Cutters (siehe mitarbeiter_auflisten)"),
  shootDate: z.string().optional().describe("Drehdatum YYYY-MM-DD"),
  shootDayId: z.string().optional().describe("ID eines bestehenden Drehtags"),
  deadline: z.string().optional().describe("Cutter-Deadline YYYY-MM-DD"),
  postingDate: z.string().optional().describe("Posting-Datum YYYY-MM-DD"),
  script: z.string().optional().describe("Skript / Konzept"),
  status: z.enum(STATUS).optional(),
};

/* ─────────────────────────── LESEN ─────────────────────────── */

server.tool("projekte_auflisten", "Alle für den Nutzer sichtbaren Projekte auflisten.", {}, async () => {
  try { return ok(await apiCall("GET", "projects.php")); } catch (e) { return fail(e); }
});

server.tool("projekt_lesen", "Ein einzelnes Projekt inkl. Dateien lesen.", { id: z.string() }, async ({ id }) => {
  try { return ok(await apiCall("GET", "projects.php", { query: { id } })); } catch (e) { return fail(e); }
});

server.tool("kunden_auflisten", "Alle Kunden auflisten (für das Kunden-Feld).", {}, async () => {
  try { return ok(await apiCall("GET", "customers.php")); } catch (e) { return fail(e); }
});

server.tool("mitarbeiter_auflisten", "Mitarbeiter (Name, Rollen, ID) auflisten — um Videograf/Cutter per Name zuzuordnen.", {}, async () => {
  try { return ok(await apiCall("GET", "users.php")); } catch (e) { return fail(e); }
});

server.tool("drehtage_auflisten", "Drehtage auflisten.", {}, async () => {
  try { return ok(await apiCall("GET", "shootdays.php")); } catch (e) { return fail(e); }
});

server.tool("posting_kalender_lesen", "Geplante & veröffentlichte Postings (Kalender) lesen.", {}, async () => {
  try { return ok(await apiCall("GET", "content_plan.php", { query: { action: "scheduled" } })); } catch (e) { return fail(e); }
});

server.tool("posting_pool_lesen", "Planbare Reels lesen (nur fertige = freigegeben, sonst alle mit Video).", { nur_fertige: z.boolean().default(true) }, async ({ nur_fertige }) => {
  try { return ok(await apiCall("GET", "content_plan.php", { query: { action: "pool", only_ready: nur_fertige ? 1 : undefined } })); } catch (e) { return fail(e); }
});

server.tool("diagnose_lesen", "Meta-/Webhook-Diagnose lesen (Admin).", {}, async () => {
  try { return ok(await apiCall("GET", "meta_diag.php")); } catch (e) { return fail(e); }
});

/* ─────────────────────────── SCHREIBEN ─────────────────────────── */

server.tool(
  "projekt_anlegen",
  "Neues Projekt/Video anlegen. Nur 'title' ist Pflicht; Kunde, Videograf, Cutter, Drehtag, Posting-Datum, Status etc. optional.",
  { title: z.string(), ...projektFelder },
  async (args) => {
    try { return ok(await apiCall("POST", "projects.php", { body: args })); } catch (e) { return fail(e); }
  }
);

server.tool(
  "projekt_aendern",
  "Felder eines bestehenden Projekts ändern (nur übergebene Felder werden geändert).",
  { id: z.string(), ...projektFelder },
  async ({ id, ...fields }) => {
    try { return ok(await apiCall("PUT", "projects.php", { query: { id }, body: fields })); } catch (e) { return fail(e); }
  }
);

server.tool(
  "projekt_status_setzen",
  "Status eines Projekts setzen (z. B. gedreht, schnitt, freigegeben, gepostet).",
  { id: z.string(), status: z.enum(STATUS) },
  async ({ id, status }) => {
    try { return ok(await apiCall("PUT", "projects.php", { query: { id }, body: { status } })); } catch (e) { return fail(e); }
  }
);

server.tool(
  "posting_planen",
  "Ein finales Video auf Datum/Uhrzeit einplanen (Posting-Planer).",
  {
    asset_id: z.string().describe("Asset-ID des finalen Videos"),
    scheduled_at: z.string().describe("Zeitpunkt 'YYYY-MM-DD HH:MM'"),
    platforms: z.array(z.string()).default(["instagram"]),
    content_type: z.string().default("reel"),
    caption: z.string().optional(),
  },
  async (args) => {
    try { return ok(await apiCall("POST", "content_plan.php", { query: { action: "plan" }, body: args })); } catch (e) { return fail(e); }
  }
);

server.tool(
  "posting_jetzt",
  "Ein finales Video SOFORT auf Instagram posten (heikel — vorher bestätigen lassen).",
  { asset_id: z.string(), caption: z.string().optional() },
  async (args) => {
    try { return ok(await apiCall("POST", "content_publish_now.php", { body: args })); } catch (e) { return fail(e); }
  }
);

/* ─────────────────────────── GENERISCHES FALLBACK ─────────────────────────── */

server.tool(
  "tool_api_call",
  "Beliebigen erlaubten API-Endpunkt aufrufen — Fallback für alles ohne eigenes Tool " +
    "(z. B. meetings.php, vacations.php, todos.php, shootdays.php, customers.php, notifications.php). " +
    "Es gelten dieselben Rechte/Log wie beim Nutzer. 'path' ist der Dateiname unter /api.",
  {
    method: z.enum(["GET", "POST", "PUT", "DELETE"]),
    path: z.string().describe("z. B. 'meetings.php' oder 'projects.php'"),
    query: z.record(z.string()).optional().describe("Query-Parameter als Objekt"),
    body: z.any().optional().describe("JSON-Body für POST/PUT"),
  },
  async ({ method, path, query, body }) => {
    try { return ok(await apiCall(method, path, { query, body })); } catch (e) { return fail(e); }
  }
);

/* ─────────────────────────── START ─────────────────────────── */

async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  process.stderr.write("[dodidis-mcp] verbunden (stdio)\n");
}

main().catch((e) => {
  process.stderr.write("[dodidis-mcp] Fatal: " + (e instanceof Error ? e.message : String(e)) + "\n");
  process.exit(1);
});
