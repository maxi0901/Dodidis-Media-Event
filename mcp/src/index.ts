#!/usr/bin/env node
/**
 * Lokaler Start (stdio) — für Claude Desktop / Claude Code.
 * Der Remote-Start (HTTP, vServer) liegt in http.ts.
 */
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { buildServer } from "./server.js";

async function main() {
  const server = buildServer();
  const transport = new StdioServerTransport();
  await server.connect(transport);
  process.stderr.write("[dodidis-mcp] verbunden (stdio)\n");
}

main().catch((e) => {
  process.stderr.write("[dodidis-mcp] Fatal: " + (e instanceof Error ? e.message : String(e)) + "\n");
  process.exit(1);
});
