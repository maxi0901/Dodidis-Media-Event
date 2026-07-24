/**
 * Schlanker HTTP-Client zur Agenturtool-API. Authentifiziert jeden Request mit
 * dem persönlichen Bearer-Token (ENV DODIDIS_API_TOKEN) → der Server handelt als
 * der zugehörige Benutzer mit dessen Rechten.
 */

export interface ApiConfig {
  baseUrl: string;
  token: string;
}

export function getConfig(): ApiConfig {
  const baseUrl = process.env.DODIDIS_API_BASE;
  const token = process.env.DODIDIS_API_TOKEN;
  if (!baseUrl) {
    throw new Error(
      "DODIDIS_API_BASE nicht gesetzt (z. B. https://dodidis-media.de/agenturtool/api)."
    );
  }
  if (!token) {
    throw new Error(
      "DODIDIS_API_TOKEN nicht gesetzt (persönlicher API-Token aus dem Profil)."
    );
  }
  return { baseUrl: baseUrl.replace(/\/+$/, ""), token };
}

export type QueryParams = Record<string, string | number | undefined | null>;

export interface ApiOptions {
  query?: QueryParams;
  body?: unknown;
}

/**
 * Ruft einen API-Endpunkt auf und gibt `data` aus der {ok,data}-Hülle zurück
 * (bzw. den rohen Body, falls keine Hülle). Wirft bei HTTP-Fehler / ok:false.
 */
export async function apiCall(
  method: string,
  path: string,
  opts: ApiOptions = {}
): Promise<unknown> {
  const cfg = getConfig();
  const url = new URL(cfg.baseUrl + "/" + path.replace(/^\/+/, ""));
  if (opts.query) {
    for (const [k, v] of Object.entries(opts.query)) {
      if (v !== undefined && v !== null && v !== "") url.searchParams.set(k, String(v));
    }
  }

  const headers: Record<string, string> = {
    Authorization: `Bearer ${cfg.token}`,
    Accept: "application/json",
  };
  let bodyStr: string | undefined;
  if (opts.body !== undefined && opts.body !== null) {
    headers["Content-Type"] = "application/json";
    bodyStr = JSON.stringify(opts.body);
  }

  let res: Response;
  try {
    res = await fetch(url, { method, headers, body: bodyStr });
  } catch (e) {
    throw new Error(`Netzwerkfehler bei ${method} ${path}: ${(e as Error).message}`);
  }

  const text = await res.text();
  let json: any;
  try {
    json = text ? JSON.parse(text) : {};
  } catch {
    throw new Error(`Ungültige Antwort (HTTP ${res.status}) von ${path}: ${text.slice(0, 200)}`);
  }

  if (!res.ok || json?.ok === false) {
    throw new Error(json?.error || `HTTP ${res.status} bei ${method} ${path}`);
  }
  return json?.data !== undefined ? json.data : json;
}
