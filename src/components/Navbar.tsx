"use client";
import { useState, useEffect } from "react";
import { motion } from "framer-motion";
import { Menu, Zap } from "lucide-react";

export default function Navbar() {
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const handleScroll = () => setScrolled(window.scrollY > 50);
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  return (
    <nav
      className="fixed top-0 left-0 right-0 z-50 px-6 py-4 transition-all duration-300"
      style={{
        backdropFilter: "blur(20px)",
        backgroundColor: scrolled ? "rgba(11, 15, 26, 0.95)" : "rgba(11, 15, 26, 0)",
        borderBottom: scrolled ? "1px solid rgba(255,255,255,0.06)" : "none",
      }}
    >
      <div className="max-w-7xl mx-auto flex items-center justify-between">
        <motion.div
          initial={{ opacity: 0, x: -20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.5 }}
          className="flex items-center gap-2"
        >
          <Zap className="text-cyan-400 w-6 h-6" />
          <span className="text-xl font-bold bg-gradient-to-r from-cyan-400 via-blue-500 to-teal-400 bg-clip-text text-transparent">
            CryptoSwap
          </span>
        </motion.div>
        <motion.button
          initial={{ opacity: 0, x: 20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.5 }}
          className="text-gray-300 hover:text-cyan-400 transition-colors"
        >
          <Menu className="w-6 h-6" />
        </motion.button>
      </div>
    </nav>
  );
}
