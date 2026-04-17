"use client";
import { motion } from "framer-motion";

const regions = [
  { flag: "🇮🇳", name: "India", status: "Full Service", color: "text-green-400", bg: "rgba(34,197,94,0.1)", border: "rgba(34,197,94,0.3)" },
  { flag: "🇲🇾", name: "Malaysia", status: "Full Service", color: "text-green-400", bg: "rgba(34,197,94,0.1)", border: "rgba(34,197,94,0.3)" },
  { flag: "🇸🇬", name: "Singapore", status: "Full Service", color: "text-green-400", bg: "rgba(34,197,94,0.1)", border: "rgba(34,197,94,0.3)" },
  { flag: "🇮🇩", name: "Indonesia", status: "Available", color: "text-blue-400", bg: "rgba(59,130,246,0.1)", border: "rgba(59,130,246,0.3)" },
  { flag: "🇹🇭", name: "Thailand", status: "Available", color: "text-blue-400", bg: "rgba(59,130,246,0.1)", border: "rgba(59,130,246,0.3)" },
  { flag: "🇭🇰", name: "Hong Kong", status: "Full Service", color: "text-green-400", bg: "rgba(34,197,94,0.1)", border: "rgba(34,197,94,0.3)" },
  { flag: "🇵🇭", name: "Philippines", status: "Expanding", color: "text-yellow-400", bg: "rgba(234,179,8,0.1)", border: "rgba(234,179,8,0.3)" },
  { flag: "🇻🇳", name: "Vietnam", status: "Expanding", color: "text-yellow-400", bg: "rgba(234,179,8,0.1)", border: "rgba(234,179,8,0.3)" },
  { flag: "🌍", name: "Global", status: "Available", color: "text-blue-400", bg: "rgba(59,130,246,0.1)", border: "rgba(59,130,246,0.3)" },
];

export default function RegionsSection() {
  return (
    <section className="py-20 px-6">
      <div className="max-w-6xl mx-auto">
        <motion.div
          initial={{ opacity: 0, y: 40 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-14"
        >
          <h2 className="text-4xl md:text-5xl font-bold text-white mb-4">
            Regions We <span className="bg-gradient-to-r from-cyan-400 to-teal-400 bg-clip-text text-transparent">Serve</span>
          </h2>
          <p className="text-gray-400 max-w-xl mx-auto">Operating across Asia and globally with dedicated regional support.</p>
        </motion.div>
        <div className="grid grid-cols-3 gap-4">
          {regions.map((region, i) => (
            <motion.div
              key={region.name}
              initial={{ opacity: 0, scale: 0.9 }}
              whileInView={{ opacity: 1, scale: 1 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.05 }}
              whileHover={{ scale: 1.05, boxShadow: `0 0 25px ${region.border}` }}
              className="p-5 rounded-2xl border flex flex-col items-center gap-3 text-center"
              style={{
                background: "rgba(15,23,42,0.6)",
                borderColor: "rgba(255,255,255,0.08)",
                backdropFilter: "blur(20px)",
              }}
            >
              <span className="text-4xl">{region.flag}</span>
              <span className="text-white font-semibold text-sm">{region.name}</span>
              <span
                className={`px-2 py-1 rounded-full text-xs font-medium ${region.color}`}
                style={{ background: region.bg, border: `1px solid ${region.border}` }}
              >
                {region.status}
              </span>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
