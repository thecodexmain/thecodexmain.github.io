"use client";
import { motion } from "framer-motion";
import { ShieldCheck, Zap, Headphones, TrendingUp, Users, Lock, BarChart2, Handshake } from "lucide-react";

const mainCards = [
  {
    icon: ShieldCheck,
    title: "Advance Payment Guarantee",
    description: "We pay you first. Every single time. No exceptions. This is our core promise to every seller.",
    glow: "rgba(6,182,212,0.3)",
  },
  {
    icon: Zap,
    title: "Zero Risk for Sellers",
    description: "Selling USDT to us carries zero financial risk. You never send crypto before receiving full payment.",
    glow: "rgba(59,130,246,0.3)",
  },
  {
    icon: TrendingUp,
    title: "Trusted Liquidity Provider",
    description: "With millions in managed liquidity, we ensure smooth, fast transactions at competitive market rates.",
    glow: "rgba(20,184,166,0.3)",
  },
];

const extraCards = [
  { icon: Zap, label: "Lightning Fast Payments" },
  { icon: Headphones, label: "24/7 Live Support" },
  { icon: BarChart2, label: "Competitive Rates" },
  { icon: Users, label: "Trusted by Hundreds" },
  { icon: Lock, label: "Privacy First" },
  { icon: Handshake, label: "Long-Term Relationships" },
];

const trustIndicators = [
  "SSL Encrypted",
  "KYC Verified Team",
  "On-Chain Verification",
  "Zero Fraud Record",
  "Regulated Operations",
];

export default function WhyChooseUs() {
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
            Why Choose <span className="bg-gradient-to-r from-cyan-400 to-teal-400 bg-clip-text text-transparent">Us</span>
          </h2>
          <p className="text-gray-400 max-w-xl mx-auto">We have built our reputation on trust, speed, and zero-risk transactions.</p>
        </motion.div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          {mainCards.map((card, i) => (
            <motion.div
              key={card.title}
              initial={{ opacity: 0, y: 40 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.1 }}
              whileHover={{ y: -8, boxShadow: `0 0 40px ${card.glow}` }}
              className="p-6 rounded-2xl border"
              style={{
                background: "rgba(15,23,42,0.6)",
                borderColor: "rgba(255,255,255,0.08)",
                backdropFilter: "blur(20px)",
              }}
            >
              <card.icon className="w-8 h-8 text-cyan-400 mb-4" />
              <h3 className="text-white font-bold text-lg mb-2">{card.title}</h3>
              <p className="text-gray-400 text-sm leading-relaxed">{card.description}</p>
            </motion.div>
          ))}
        </div>

        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-10">
          {extraCards.map((card, i) => (
            <motion.div
              key={card.label}
              initial={{ opacity: 0, scale: 0.9 }}
              whileInView={{ opacity: 1, scale: 1 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.05 }}
              whileHover={{ scale: 1.05 }}
              className="p-4 rounded-xl border flex flex-col items-center gap-2 text-center"
              style={{
                background: "rgba(15,23,42,0.4)",
                borderColor: "rgba(255,255,255,0.06)",
              }}
            >
              <card.icon className="w-5 h-5 text-cyan-400" />
              <span className="text-xs text-gray-400">{card.label}</span>
            </motion.div>
          ))}
        </div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="flex flex-wrap justify-center gap-4"
        >
          {trustIndicators.map((item) => (
            <span
              key={item}
              className="px-4 py-2 rounded-full text-xs font-medium text-cyan-400 border"
              style={{
                borderColor: "rgba(6,182,212,0.3)",
                background: "rgba(6,182,212,0.08)",
              }}
            >
              ✓ {item}
            </span>
          ))}
        </motion.div>
      </div>
    </section>
  );
}
