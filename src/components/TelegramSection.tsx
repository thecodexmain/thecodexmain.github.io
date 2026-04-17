"use client";
import { motion } from "framer-motion";
import { MessageCircle, CheckCircle } from "lucide-react";

const features = [
  "Instant response from our team",
  "Real-time rates & availability",
  "Secure end-to-end encrypted chat",
  "24/7 availability, no downtime",
];

export default function TelegramSection() {
  return (
    <section className="py-20 px-6">
      <div className="max-w-3xl mx-auto text-center">
        <motion.div
          initial={{ opacity: 0, y: 40 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
        >
          <h2 className="text-4xl md:text-5xl font-bold text-white mb-4">
            Connect Instantly on{" "}
            <span className="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">Telegram</span>
          </h2>
          <p className="text-gray-400 mb-10">Our Telegram bot is available 24/7 for instant assistance and rate confirmations.</p>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 40 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ delay: 0.1 }}
          className="p-8 rounded-2xl border mb-8"
          style={{
            background: "rgba(15,23,42,0.6)",
            borderColor: "rgba(6,182,212,0.3)",
            backdropFilter: "blur(20px)",
            boxShadow: "0 0 40px rgba(6,182,212,0.1)",
          }}
        >
          <MessageCircle className="w-16 h-16 text-cyan-400 mx-auto mb-4" />
          <p className="text-white font-bold text-xl mb-2">@CryptoSwap2026_Bot</p>
          <p className="text-gray-400 text-sm mb-6">Your trusted USDT exchange partner</p>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-8 text-left">
            {features.map((f) => (
              <div key={f} className="flex items-center gap-2">
                <CheckCircle className="w-4 h-4 text-cyan-400 shrink-0" />
                <span className="text-gray-300 text-sm">{f}</span>
              </div>
            ))}
          </div>

          <motion.a
            href="https://t.me/CryptoSwap2026_Bot"
            target="_blank"
            rel="noopener noreferrer"
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.98 }}
            className="inline-flex items-center gap-2 px-10 py-4 rounded-xl text-white font-semibold text-lg"
            style={{
              background: "linear-gradient(135deg, #06b6d4, #3b82f6)",
              boxShadow: "0 0 30px rgba(6,182,212,0.5)",
            }}
          >
            <MessageCircle className="w-5 h-5" />
            Open Telegram Bot
          </motion.a>
        </motion.div>
      </div>
    </section>
  );
}
