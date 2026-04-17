"use client";
import { motion } from "framer-motion";
import { MessageCircle } from "lucide-react";

export default function CTASection() {
  return (
    <section className="py-20 px-6">
      <div className="max-w-3xl mx-auto text-center">
        <motion.div
          initial={{ opacity: 0, y: 40 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="p-12 rounded-3xl border"
          style={{
            background: "linear-gradient(135deg, rgba(6,182,212,0.08), rgba(59,130,246,0.08), rgba(20,184,166,0.08))",
            borderColor: "rgba(6,182,212,0.2)",
            backdropFilter: "blur(20px)",
          }}
        >
          <h2 className="text-4xl md:text-5xl font-extrabold text-white mb-4">
            Ready to exchange your{" "}
            <span className="bg-gradient-to-r from-cyan-400 via-blue-500 to-teal-400 bg-clip-text text-transparent">
              USDT?
            </span>
          </h2>
          <p className="text-gray-400 mb-10 text-lg">Join hundreds of satisfied sellers. Get paid first, send crypto second.</p>
          <motion.a
            href="https://t.me/CryptoSwap2026_Bot"
            target="_blank"
            rel="noopener noreferrer"
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.98 }}
            className="inline-flex items-center gap-3 px-12 py-5 rounded-xl text-white font-bold text-xl"
            style={{
              background: "linear-gradient(135deg, #06b6d4, #3b82f6, #14b8a6)",
              boxShadow: "0 0 50px rgba(6,182,212,0.5)",
            }}
          >
            <MessageCircle className="w-6 h-6" />
            Get Started on Telegram
          </motion.a>
        </motion.div>
      </div>
    </section>
  );
}
