import { db } from "@/db";
import { sql } from "drizzle-orm";

export const dynamic = "force-dynamic";

export default async function HomePage() {
  // Validación de conexión con la base de datos
  await db.execute(sql`select 1`);

  return (
    <main className="grid min-h-screen place-items-center bg-slate-50 px-6 py-12">
      <section className="w-full max-w-2xl rounded-3xl bg-white p-10 shadow-[0_24px_60px_rgba(16,24,40,0.12)]">
        <p className="m-0 text-sm font-semibold uppercase tracking-[0.08em] text-indigo-600">
          Nexu Web Service
        </p>
        <h1 className="mt-4 text-[clamp(2rem,5vw,3.25rem)] font-bold leading-[1.05] text-slate-950">
          ¡Bienvenido a tu aplicación!
        </h1>
        <p className="mt-4 text-base text-slate-600 leading-relaxed">
          Tu servicio web ya está conectado correctamente a la base de datos PostgreSQL.
        </p>
        
        <div className="mt-8 flex items-center gap-3 rounded-2xl bg-slate-100 p-4 text-sm text-slate-700">
          <span className="relative flex h-3 w-3">
            <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
            <span className="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
          </span>
          Base de datos Drizzle/PostgreSQL activa y respondiendo.
        </div>
      </section>
    </main>
  );
}
