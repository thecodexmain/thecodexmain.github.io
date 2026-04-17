"use client";
import { motion } from "framer-motion";
import { MessageCircle, ChevronDown } from "lucide-react";

export default function HeroSection() {
  return (
    <section className="relative min-h-screen flex items-center justify-center pt-20 pb-16 overflow-hidden">
      {/* Animated background blob */}
      <div
        className="absolute inset-0 pointer-events-none"
        aria-hidden="true"
      >
        <div className="absolute top-1/4 left-1/4 w-96 h-96 rounded-full blur-3xl opacity-20 animate-float"
          style={{ background: "radial-gradient(circle, rgba(6,182,212,0.6) 0%, transparent 70%)" }}
        />
        <div className="absolute bottom-1/4 right-1/4 w-80 h-80 rounded-full blur-3xl opacity-15 animate-float"
          style={{ background: "radial-gradient(circle, rgba(59,130,246,0.6) 0%, transparent 70%)", animationDelay: "3s" }}
        />
      </div>

      {/* Floating particles */}
      {[...Array(12)].map((_, i) => (
        <div
          key={i}
          className="absolute w-1 h-1 rounded-full bg-cyan-400 opacity-40 animate-float"
          style={{
            left: `${10 + i * 8}%`,
            top: `${20 + (i % 4) * 20}%`,
            animationDelay: `${i * 0.5}s`,
            animationDuration: `${4 + (i % 3)}s`,
          }}
        />
      ))}

      <div className="relative z-10 max-w-5xl mx-auto px-6 text-center">
        {/* Badge */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6 }}
          className="inline-flex items-center gap-2 px-4 py-2 rounded-full border mb-8"
          style={{
            borderColor: "rgba(6,182,212,0.5)",
            background: "rgba(6,182,212,0.1)",
            boxShadow: "0 0 20px rgba(6,182,212,0.2)",
          }}
        >
          <span className="w-2 h-2 rounded-full bg-cyan-400 animate-pulse" />
          <span className="text-cyan-400 text-sm font-medium">Advance Payment Guaranteed</span>
        </motion.div>

        {/* Title */}
        <motion.h1
          initial={{ opacity: 0, y: 30 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.7, delay: 0.1 }}
          className="text-5xl md:text-7xl font-extrabold leading-tight mb-6"
        >
          <span className="bg-gradient-to-r from-cyan-400 via-blue-500 to-teal-400 bg-clip-text text-transparent">
            Sell USDT
          </span>
          <br />
          <span className="text-white">with 100%</span>
          <br />
          <span className="text-white">Advance Payment</span>
          <br />
          <span className="bg-gradient-to-r from-teal-400 to-cyan-400 bg-clip-text text-transparent">
            Security
          </span>
        </motion.h1>

        {/* Subtitle */}
        <motion.p
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.7, delay: 0.2 }}
          className="text-gray-400 text-lg md:text-xl max-w-2xl mx-auto mb-10"
        >
          We pay you <span className="text-cyan-400 font-semibold">before</span> you transfer any USDT. No risk,
          no hassle — just fast, secure, and verified crypto exchange.
        </motion.p>

        {/* Buttons */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.7, delay: 0.3 }}
          className="flex flex-col sm:flex-row gap-4 justify-center"
        >
          <motion.a
            href="https://t.me/CryptoSwap2026_Bot"
            target="_blank"
            rel="noopener noreferrer"
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.98 }}
            className="flex items-center justify-center gap-2 px-8 py-4 rounded-xl text-white font-semibold text-lg"
            style={{
              background: "linear-gradient(135deg, #06b6d4, #3b82f6, #14b8a6)",
              boxShadow: "0 0 30px rgba(6,182,212,0.4)",
            }}
          >
            <MessageCircle className="w-5 h-5" />
            Contact on Telegram
          </motion.a>
          <motion.a
            href="#how-it-works"
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.98 }}
            className="flex items-center justify-center gap-2 px-8 py-4 rounded-xl font-semibold text-lg border text-gray-300 hover:text-white transition-colors"
            style={{
              borderColor: "rgba(255,255,255,0.15)",
              background: "rgba(255,255,255,0.05)",
            }}
          >
            <ChevronDown className="w-5 h-5" />
            How It Works
          </motion.a>
        </motion.div>
      </div>
    </section>
  );
}
