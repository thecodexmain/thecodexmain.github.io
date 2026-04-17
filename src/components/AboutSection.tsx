"use client";
import { motion } from "framer-motion";
import { Globe, BadgeCheck, Users, Banknote } from "lucide-react";
import FeatureCard from "./FeatureCard";

const features = [
  {
    icon: Globe,
    title: "China-Based Operations",
    description: "Our operations are grounded in China, giving us unmatched access to Asian crypto markets with regulatory awareness.",
  },
  {
    icon: BadgeCheck,
    title: "Verified & Trusted",
    description: "Every transaction is handled by verified professionals. We maintain strict identity and compliance checks.",
    iconColor: "text-blue-400",
  },
  {
    icon: Users,
    title: "Global Client Base",
    description: "Serving hundreds of clients across Asia and beyond. Our reputation is built on consistent, reliable payments.",
    iconColor: "text-teal-400",
  },
  {
    icon: Banknote,
    title: "Strong Liquidity",
    description: "With deep liquidity reserves, we can handle large USDT volumes and pay you instantly without delays.",
    iconColor: "text-green-400",
  },
];

export default function AboutSection() {
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
            About <span className="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">CryptoSwap</span>
          </h2>
          <p className="text-gray-400 max-w-xl mx-auto">Professional USDT exchange with a proven track record of reliability and security.</p>
        </motion.div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {features.map((f, i) => (
            <motion.div
              key={f.title}
              initial={{ opacity: 0, y: 40 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.1 }}
            >
              <FeatureCard {...f} />
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
