"use client";
import { motion } from "framer-motion";
import { CheckCircle, UserCheck, ShieldCheck, ScanFace } from "lucide-react";

const cards = [
  { icon: UserCheck, title: "Verified Clients Only", description: "We only transact with verified, trusted individuals. New clients go through a quick onboarding process." },
  { icon: ShieldCheck, title: "Transfer After Confirmation", description: "You transfer USDT only after confirming you've received the full payment. Zero risk to sellers." },
  { icon: ScanFace, title: "Identity Verification", description: "Basic KYC for all new clients ensures a safe environment and protects both parties." },
];

const steps = [
  "Contact us on Telegram",
  "Get paid first (advance)",
  "Confirm receipt of funds",
  "Send crypto to our wallet",
  "Transaction complete ✓",
];

export default function PaymentModelSection() {
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
            Advance Payment <span className="bg-gradient-to-r from-cyan-400 to-teal-400 bg-clip-text text-transparent">Model</span>
          </h2>
          <p className="text-gray-400 max-w-xl mx-auto">Our unique model ensures sellers are always protected. You receive payment before sending USDT.</p>
        </motion.div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
          {cards.map((card, i) => (
            <motion.div
              key={card.title}
              initial={{ opacity: 0, y: 40 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.1 }}
              whileHover={{ y: -8 }}
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

        <motion.div
          initial={{ opacity: 0, y: 40 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="p-8 rounded-2xl border"
          style={{
            background: "rgba(15,23,42,0.6)",
            borderColor: "rgba(6,182,212,0.2)",
            backdropFilter: "blur(20px)",
          }}
        >
          <h3 className="text-white font-bold text-xl mb-6 text-center">Promise Checklist</h3>
          <div className="flex flex-col md:flex-row items-center justify-center gap-4 flex-wrap">
            {steps.map((step, i) => (
              <div key={i} className="flex items-center gap-2">
                <CheckCircle className="w-5 h-5 text-green-400 shrink-0" />
                <span className="text-gray-300 text-sm">{step}</span>
                {i < steps.length - 1 && (
                  <span className="hidden md:block text-gray-600 ml-2">→</span>
                )}
              </div>
            ))}
          </div>
        </motion.div>
      </div>
    </section>
  );
}
