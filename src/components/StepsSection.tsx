"use client";
import { motion } from "framer-motion";
import { MessageCircle, Wallet, Send } from "lucide-react";

const steps = [
  {
    num: "01",
    icon: MessageCircle,
    title: "Contact & Confirm Rate",
    description: "Reach us on Telegram. We'll confirm the current USDT rate and amount you want to sell.",
    color: "cyan",
  },
  {
    num: "02",
    icon: Wallet,
    title: "Receive Advance Payment",
    description: "We send you the full payment BEFORE you transfer any USDT. 100% advance guarantee.",
    color: "green",
    highlight: true,
  },
  {
    num: "03",
    icon: Send,
    title: "Send USDT After Payment",
    description: "Once you confirm receipt of payment, send the agreed USDT amount to our wallet.",
    color: "teal",
  },
];

export default function StepsSection() {
  return (
    <section id="how-it-works" className="py-20 px-6">
      <div className="max-w-5xl mx-auto">
        <motion.div
          initial={{ opacity: 0, y: 40 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-14"
        >
          <h2 className="text-4xl md:text-5xl font-bold text-white mb-4">
            How It <span className="bg-gradient-to-r from-cyan-400 to-teal-400 bg-clip-text text-transparent">Works</span>
          </h2>
          <p className="text-gray-400 max-w-xl mx-auto">Three simple steps to sell your USDT safely and securely.</p>
        </motion.div>
        <div className="flex flex-col md:flex-row items-start gap-8 relative">
          {/* Connecting line */}
          <div className="hidden md:block absolute top-12 left-1/6 right-1/6 h-px bg-gradient-to-r from-cyan-500/30 via-blue-500/50 to-teal-500/30" />

          {steps.map((step, i) => (
            <motion.div
              key={step.num}
              initial={{ opacity: 0, y: 40 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.2 }}
              className="flex-1 flex flex-col items-center text-center gap-4"
            >
              <div
                className="relative w-20 h-20 rounded-full flex items-center justify-center text-2xl font-bold z-10"
                style={{
                  background: step.highlight
                    ? "linear-gradient(135deg, rgba(34,197,94,0.3), rgba(20,184,166,0.3))"
                    : "rgba(6,182,212,0.15)",
                  border: `2px solid ${step.highlight ? "rgba(34,197,94,0.6)" : "rgba(6,182,212,0.4)"}`,
                  boxShadow: step.highlight ? "0 0 30px rgba(34,197,94,0.3)" : "0 0 20px rgba(6,182,212,0.2)",
                }}
              >
                <step.icon className={`w-8 h-8 ${step.highlight ? "text-green-400" : "text-cyan-400"}`} />
              </div>
              <div
                className="px-3 py-1 rounded-full text-xs font-bold"
                style={{
                  background: step.highlight ? "rgba(34,197,94,0.15)" : "rgba(6,182,212,0.15)",
                  color: step.highlight ? "#4ade80" : "#22d3ee",
                }}
              >
                STEP {step.num}
              </div>
              <h3 className="text-white font-bold text-xl">{step.title}</h3>
              <p className="text-gray-400 text-sm leading-relaxed max-w-xs">{step.description}</p>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
