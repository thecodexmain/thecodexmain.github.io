"use client";
import { motion } from "framer-motion";
import { Shield, Lock, TrendingUp, CheckCircle } from "lucide-react";

const badges = [
  { icon: Shield, label: "SSL Secured", color: "text-cyan-400" },
  { icon: Lock, label: "Advance Payment", color: "text-blue-400" },
  { icon: TrendingUp, label: "Best Rates", color: "text-teal-400" },
  { icon: CheckCircle, label: "Zero Risk", color: "text-green-400" },
];

export default function TrustBadges() {
  return (
    <section className="py-10 px-6">
      <div className="max-w-5xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-4">
        {badges.map((badge, i) => (
          <motion.div
            key={badge.label}
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: i * 0.1 }}
            whileHover={{ scale: 1.05 }}
            className="flex flex-col items-center gap-2 p-4 rounded-2xl border text-center"
            style={{
              background: "rgba(15,23,42,0.6)",
              borderColor: "rgba(255,255,255,0.08)",
              backdropFilter: "blur(20px)",
            }}
          >
            <badge.icon className={`w-6 h-6 ${badge.color}`} />
            <span className="text-sm font-medium text-gray-300">{badge.label}</span>
          </motion.div>
        ))}
      </div>
    </section>
  );
}
