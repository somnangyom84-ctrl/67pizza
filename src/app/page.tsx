import { db } from "@/db";
import { sql } from "drizzle-orm";

export const dynamic = "force-dynamic";

const YELLOW = "#FFD629";

const categories = [
  { name: "Burgers", icon: "🍔" },
  { name: "Pizza", icon: "🍕" },
  { name: "Pasta", icon: "🍝" },
  { name: "Fries", icon: "🍟" },
  { name: "Drinks", icon: "🥤" },
  { name: "Desserts", icon: "🍰" },
];

const products = [
  { name: "Classic Burger", price: "12,000 ៛", img: "/images/classic-burger.jpg", cat: "Burgers" },
  { name: "Cheese Burger", price: "14,000 ៛", img: "/images/cheese-burger.jpg", cat: "Burgers" },
  { name: "Chicken Tikka Pizza", price: "28,000 ៛", img: "/images/chicken-tikka-pizza.jpg", cat: "Pizza" },
  { name: "Pepperoni Pizza", price: "26,000 ៛", img: "/images/pepperoni-pizza.jpg", cat: "Pizza" },
  { name: "Creamy Pasta", price: "18,000 ៛", img: "/images/creamy-pasta.jpg", cat: "Pasta" },
  { name: "Masala Fries", price: "8,000 ៛", img: "/images/masala-fries.jpg", cat: "Fries" },
  { name: "Iced Coffee", price: "7,000 ៛", img: "/images/iced-coffee.jpg", cat: "Drinks" },
  { name: "Chocolate Lava Cake", price: "12,000 ៛", img: "/images/chocolate-lava-cake.jpg", cat: "Desserts" },
];

const restaurants = [
  { name: "Burger Hub", rating: "4.5 (1.2k)", dist: "0.5 km", img: "/images/classic-burger.jpg" },
  { name: "Pizza Planet", rating: "4.6 (850)", dist: "1.2 km", img: "/images/pepperoni-pizza.jpg" },
  { name: "Pasta House", rating: "4.4 (620)", dist: "2.0 km", img: "/images/creamy-pasta.jpg" },
  { name: "Chai & More", rating: "4.3 (430)", dist: "2.5 km", img: "/images/iced-coffee.jpg" },
];

const deliverables = [
  ["database/67pizza_db.sql", "15 tables, FKs, indexes, seed data"],
  ["config/", "PDO connection + secure session config"],
  ["includes/", "functions, auth, csrf, validation, layout"],
  ["actions/", "login, register, cart, checkout, contact…"],
  ["19 public/customer pages", "menu, product, cart, checkout, orders…"],
  ["admin/ (13 pages)", "dashboard, products, orders, coupons, reports…"],
];

export default async function HomePage() {
  await db.execute(sql`select 1`);

  return (
    <main className="mx-auto max-w-6xl px-4 pb-16">
      {/* Header */}
      <header className="flex items-center justify-between gap-4 py-4">
        <div className="flex items-center gap-2 text-2xl font-extrabold">
          <span className="-rotate-3 rounded-lg px-2 py-0.5 text-black" style={{ background: YELLOW }}>
            67
          </span>
          <span className="italic" style={{ color: YELLOW }}>
            pizza
          </span>
          <span className="ml-3 hidden border-l border-white/10 pl-3 text-sm font-normal italic text-white/70 sm:inline">
            Good Food, Happy Mood
          </span>
        </div>
        <div className="flex gap-2">
          {["🔔", "👤"].map((i) => (
            <span key={i} className="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-[#1b1b1f]">
              {i}
            </span>
          ))}
        </div>
      </header>

      {/* Search */}
      <div className="flex items-center gap-3 rounded-full border border-white/10 bg-[#1b1b1f] px-5 py-3.5 text-white/50">
        <span>🔍</span>
        <span className="flex-1">Search for food, restaurant or items...</span>
        <span>⚙️</span>
      </div>

      {/* Hero */}
      <section className="relative mt-5 flex min-h-[380px] items-center overflow-hidden rounded-3xl border border-white/10 shadow-2xl">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src="/images/hero.jpg" alt="Burger, fries and cola" className="absolute inset-0 h-full w-full object-cover object-right" />
        <div className="absolute inset-0 bg-gradient-to-r from-[#0a0a0c] via-[#0a0a0c]/80 to-transparent" />
        <div className="relative max-w-md p-8 sm:p-12">
          <p className="text-3xl font-bold italic" style={{ color: YELLOW }}>
            Delicious Food
          </p>
          <h1 className="mt-1 text-5xl font-extrabold leading-[1.05] sm:text-6xl">
            Delivered
            <br />
            <span className="italic" style={{ color: YELLOW }}>
              Fresh
            </span>
          </h1>
          <p className="mt-4 text-lg tracking-wide text-white/80">Hot • Fresh • On Time</p>
          <span className="mt-6 inline-flex items-center gap-2 rounded-full px-6 py-3 font-bold text-black" style={{ background: YELLOW }}>
            Order Now →
          </span>
        </div>
      </section>

      {/* Categories */}
      <section className="mt-10">
        <div className="grid grid-cols-3 gap-3 md:grid-cols-6">
          {categories.map((c) => (
            <div key={c.name} className="rounded-2xl border border-white/10 bg-[#1b1b1f] p-5 text-center transition hover:-translate-y-1 hover:border-[#FFD629]">
              <div className="text-3xl">{c.icon}</div>
              <div className="mt-2 font-semibold">{c.name}</div>
            </div>
          ))}
        </div>
      </section>

      {/* Popular */}
      <section className="mt-10">
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-xl font-bold">🔥 Popular Right Now</h2>
          <span className="text-sm text-white/80">See All →</span>
        </div>
        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
          {products.map((p) => (
            <article key={p.name} className="overflow-hidden rounded-2xl border border-white/10 bg-[#1b1b1f] transition hover:-translate-y-1">
              <div className="relative aspect-[1/.85] overflow-hidden">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={p.img} alt={p.name} className="h-full w-full object-cover" />
                <span className="absolute right-3 top-3 grid h-9 w-9 place-items-center rounded-full bg-black/60">♡</span>
              </div>
              <div className="p-4">
                <div className="text-xs text-white/50">{p.cat}</div>
                <h3 className="font-semibold">{p.name}</h3>
                <div className="mt-2 flex items-center justify-between">
                  <span className="font-bold" style={{ color: YELLOW }}>
                    {p.price}
                  </span>
                  <span className="grid h-9 w-9 place-items-center rounded-full text-black" style={{ background: YELLOW }}>
                    +
                  </span>
                </div>
              </div>
            </article>
          ))}
        </div>
      </section>

      {/* Offer */}
      <section className="relative mt-10 flex min-h-[260px] items-center overflow-hidden rounded-3xl border border-white/10">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src="/images/offer.jpg" alt="Special offer" className="absolute inset-0 h-full w-full object-cover object-right" />
        <div className="absolute inset-0 bg-gradient-to-r from-[#0a0a0c] via-[#0a0a0c]/75 to-transparent" />
        <div className="relative p-8 sm:p-10">
          <span className="inline-block -rotate-2 rounded px-4 py-1 text-2xl font-bold italic text-black" style={{ background: YELLOW }}>
            Special Offer
          </span>
          <h2 className="mt-4 text-4xl font-extrabold">
            Get <span style={{ color: YELLOW }}>20% OFF</span>
          </h2>
          <p className="text-white/80">On your first order with code WELCOME20</p>
        </div>
      </section>

      {/* Restaurants */}
      <section className="mt-10">
        <h2 className="mb-4 text-xl font-bold">📍 Top Restaurants Near You</h2>
        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
          {restaurants.map((r) => (
            <div key={r.name} className="overflow-hidden rounded-2xl border border-white/10 bg-[#1b1b1f]">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={r.img} alt={r.name} className="aspect-[16/10] w-full object-cover" />
              <div className="p-4">
                <h3 className="font-semibold">{r.name} ›</h3>
                <div className="mt-1 flex justify-between text-xs text-white/50">
                  <span>
                    <span style={{ color: YELLOW }}>★</span> {r.rating}
                  </span>
                  <span>{r.dist}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* Deliverable info */}
      <section className="mt-12 rounded-3xl border border-white/10 bg-[#141417] p-8">
        <h2 className="text-2xl font-extrabold">
          This is a design preview — the real app is <span style={{ color: YELLOW }}>PHP 8 + MySQL</span>
        </h2>
        <p className="mt-2 max-w-3xl text-white/70">
          The complete, database-driven 67pizza project is in the <code className="rounded bg-black/40 px-1.5 py-0.5">67pizza/</code> folder of this workspace. Copy it to{" "}
          <code className="rounded bg-black/40 px-1.5 py-0.5">C:\xampp\htdocs\67pizza\</code>, import{" "}
          <code className="rounded bg-black/40 px-1.5 py-0.5">database/67pizza_db.sql</code> in phpMyAdmin, run{" "}
          <code className="rounded bg-black/40 px-1.5 py-0.5">admin/setup-admin.php</code> once, then open{" "}
          <code className="rounded bg-black/40 px-1.5 py-0.5">http://localhost/67pizza/</code>. Full instructions are in{" "}
          <code className="rounded bg-black/40 px-1.5 py-0.5">67pizza/README.md</code>.
        </p>
        <ul className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {deliverables.map(([k, v]) => (
            <li key={k} className="rounded-2xl border border-white/10 bg-[#1b1b1f] p-4">
              <div className="font-mono text-sm" style={{ color: YELLOW }}>
                {k}
              </div>
              <div className="mt-1 text-sm text-white/60">{v}</div>
            </li>
          ))}
        </ul>
      </section>

      <footer className="mt-10 flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-6 text-sm text-white/50">
        <span>© {new Date().getFullYear()} 67pizza</span>
        <span className="flex flex-wrap gap-4">
          <a className="hover:text-white" href="tel:0967505304">
            📞 0967505304
          </a>
          <a className="hover:text-white" href="mailto:somnangyom84@gmail.com">
            ✉️ somnangyom84@gmail.com
          </a>
          <a className="hover:text-white" href="https://t.me/yomsomnang" target="_blank" rel="noopener noreferrer">
            Telegram @yomsomnang
          </a>
          <a className="hover:text-white" href="https://www.facebook.com/share/19VTbRJJao/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer">
            Facebook
          </a>
        </span>
      </footer>
    </main>
  );
}
