"use client";
import { motion } from "framer-motion";
import { AlertTriangle, ShieldX } from "lucide-react";

export default function WarningSection() {
  return (
    <section className="py-20 px-6">
      <div className="max-w-4xl mx-auto">
        <motion.div
          initial={{ opacity: 0, y: 40 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="p-8 rounded-2xl"
          style={{
            background: "rgba(15,23,42,0.8)",
            border: "1px solid rgba(239,68,68,0.4)",
            boxShadow: "0 0 40px rgba(239,68,68,0.15)",
          }}
        >
          <div className="flex items-center gap-3 mb-6">
            <AlertTriangle className="w-8 h-8 text-red-400" />
            <h2 className="text-2xl md:text-3xl font-bold text-white">Important Notice & Warnings</h2>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div
              className="p-5 rounded-xl"
              style={{ background: "rgba(239,68,68,0.08)", border: "1px solid rgba(239,68,68,0.2)" }}
            >
              <ShieldX className="w-6 h-6 text-red-400 mb-3" />
              <h3 className="text-white font-bold mb-2">Verified Clients Only</h3>
              <p className="text-gray-300 text-sm leading-relaxed">
                We only work with verified, trusted individuals. Unverified users attempting to transact will be permanently blocked. All interactions are monitored.
              </p>
            </div>
            <div
              className="p-5 rounded-xl"
              style={{ background: "rgba(239,68,68,0.08)", border: "1px solid rgba(239,68,68,0.2)" }}
            >
              <AlertTriangle className="w-6 h-6 text-red-400 mb-3" />
              <h3 className="text-white font-bold mb-2">Zero Tolerance for Fraud</h3>
              <p className="text-gray-300 text-sm leading-relaxed">
                Any fraudulent activity, false claims, or attempts to deceive will be reported to authorities and all associated parties will be blacklisted permanently.
              </p>
            </div>
          </div>
        </motion.div>
      </div>
    </section>
  );
}
