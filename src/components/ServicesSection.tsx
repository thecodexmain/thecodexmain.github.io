"use client";
import { motion } from "framer-motion";
import { Building2, Landmark, HandCoins } from "lucide-react";

const services = [
  {
    icon: Building2,
    badge: "Most Popular",
    badgeColor: "bg-cyan-500/20 text-cyan-400 border-cyan-500/30",
    title: "Bank Transfer",
    description: "Secure bank-to-bank transfers directly to your account. Ideal for large USDT volumes.",
    time: "1-2 hours",
    glow: "rgba(6,182,212,0.3)",
  },
  {
    icon: Landmark,
    badge: "Fast",
    badgeColor: "bg-blue-500/20 text-blue-400 border-blue-500/30",
    title: "CDM Deposit",
    description: "Cash deposit machine payments. Perfect for clients who prefer physical bank deposits.",
    time: "30-60 min",
    glow: "rgba(59,130,246,0.3)",
  },
  {
    icon: HandCoins,
    badge: "Instant",
    badgeColor: "bg-teal-500/20 text-teal-400 border-teal-500/30",
    title: "Physical Cash",
    description: "In-person cash transactions for verified clients in supported regions.",
    time: "Instant",
    glow: "rgba(20,184,166,0.3)",
  },
];

export default function ServicesSection() {
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
            Payment <span className="bg-gradient-to-r from-cyan-400 to-teal-400 bg-clip-text text-transparent">Methods</span>
          </h2>
          <p className="text-gray-400 max-w-xl mx-auto">Multiple secure payment options tailored to your preference and region.</p>
        </motion.div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {services.map((s, i) => (
            <motion.div
              key={s.title}
              initial={{ opacity: 0, y: 40 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.15 }}
              whileHover={{ y: -8, boxShadow: `0 0 40px ${s.glow}` }}
              className="p-6 rounded-2xl border flex flex-col gap-4"
              style={{
                background: "rgba(15,23,42,0.6)",
                borderColor: "rgba(255,255,255,0.08)",
                backdropFilter: "blur(20px)",
              }}
            >
              <div className="flex items-start justify-between">
                <div
                  className="w-12 h-12 rounded-xl flex items-center justify-center"
                  style={{ background: `${s.glow.replace("0.3", "0.15")}` }}
                >
                  <s.icon className="w-6 h-6 text-cyan-400" />
                </div>
                <span className={`px-2 py-1 rounded-full text-xs font-semibold border ${s.badgeColor}`}>
                  {s.badge}
                </span>
              </div>
              <h3 className="text-white font-bold text-xl">{s.title}</h3>
              <p className="text-gray-400 text-sm leading-relaxed">{s.description}</p>
              <div className="mt-auto pt-4 border-t border-white/5">
                <span className="text-xs text-gray-500">Processing: </span>
                <span className="text-xs text-cyan-400 font-medium">{s.time}</span>
              </div>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
