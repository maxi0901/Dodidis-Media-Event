#!/usr/bin/env node
/**
 * Remote-Start (Streamable HTTP) — für den vServer, damit der Server als
 * „Benutzerdefinierter Connector" (Remote MCP Server URL) in claude.ai/Desktop
 * eingetragen werden kann.
 *
 * Absicherung: Da das claude.ai-Connector-Fenster keine eigenen Header, sondern
 * nur eine URL (+ optional OAuth) erlaubt, dient ein GEHEIMES URL-Segment als
 * Schlüssel (analog zu den Kalender-Abo-Links): der Endpunkt ist nur unter
 *   https://<host>/mcp/<MCP_URL_SECRET>
 * erreichbar. Über HTTPS ist die URL im Transport geschützt. Der Server ruft die
 * Agenturtool-API mit dem konfigurierten persönlichen Token (DODIDIS_API_TOKEN)
 * auf — handelt also als dieser Nutzer.
 *
 * ENV: DODIDIS_API_BASE, DODIDIS_API_TOKEN, MCP_URL_SECRET, PORT (optional).
 */
import express, { type Request, type Response } from "express";
import { randomUUID } from "node:crypto";
import { StreamableHTTPServerTransport } from "@modelcontextprotocol/sdk/server/streamableHttp.js";
import { isInitializeRequest } from "@modelcontextprotocol/sdk/types.js";
import { buildServer } from "./server.js";

const PORT = parseInt(process.env.PORT || "8787", 10);
const SECRET = process.env.MCP_URL_SECRET || "";
if (!SECRET || SECRET.length < 16) {
  process.stderr.write("[dodidis-mcp] MCP_URL_SECRET fehlt oder ist zu kurz (min. 16 Zeichen).\n");
  process.exit(1);
}

const app = express();
app.use(express.json({ limit: "4mb" }));

// Aktive Sessions je Session-ID (Streamable HTTP hält den Zustand pro Client).
const transports: Record<string, StreamableHTTPServerTransport> = {};

function badSecret(req: Request, res: Response): boolean {
  if (req.params.secret !== SECRET) {
    res.status(401).json({ jsonrpc: "2.0", error: { code: -32001, message: "Unauthorized" }, id: null });
    return true;
  }
  return false;
}

// Initialisierung + normale Requests
app.post("/mcp/:secret", async (req: Request, res: Response) => {
  if (badSecret(req, res)) return;
  const sid = req.headers["mcp-session-id"] as string | undefined;
  let transport = sid ? transports[sid] : undefined;

  if (!transport) {
    if (sid || !isInitializeRequest(req.body)) {
      res.status(400).json({ jsonrpc: "2.0", error: { code: -32000, message: "Keine gültige Session (erst initialize senden)." }, id: null });
      return;
    }
    transport = new StreamableHTTPServerTransport({
      sessionIdGenerator: () => randomUUID(),
      onsessioninitialized: (id) => { transports[id] = transport!; },
    });
    transport.onclose = () => { if (transport!.sessionId) delete transports[transport!.sessionId]; };
    const server = buildServer();
    await server.connect(transport);
  }

  await transport.handleRequest(req, res, req.body);
});

// SSE-Stream (GET) + Session schließen (DELETE)
async function sessionRequest(req: Request, res: Response) {
  if (badSecret(req, res)) return;
  const sid = req.headers["mcp-session-id"] as string | undefined;
  const transport = sid ? transports[sid] : undefined;
  if (!transport) { res.status(400).send("Unbekannte oder fehlende Session-ID."); return; }
  await transport.handleRequest(req, res);
}
app.get("/mcp/:secret", sessionRequest);
app.delete("/mcp/:secret", sessionRequest);

// Health-Check (ohne Secret) für Monitoring/Caddy.
app.get("/health", (_req: Request, res: Response) => { res.json({ ok: true }); });

app.listen(PORT, () => {
  process.stderr.write(`[dodidis-mcp] HTTP-Transport lauscht auf :${PORT} (Pfad /mcp/<secret>)\n`);
});
