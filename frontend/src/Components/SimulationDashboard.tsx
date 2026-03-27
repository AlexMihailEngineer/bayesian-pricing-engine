import React, { useState, useEffect } from 'react';

interface Experiment {
    price: number;
    expected_conversion_rate: number;
    expected_revenue: number;
    uncertainty_index: number;
}

export default function SimulationDashboard({ productId }: { productId: string }) {
    const [data, setData] = useState<{ optimal_price: number; all_experiments: Experiment[] } | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch(`/api/recommendation/${productId}`)
            .then(res => res.json())
            .then(json => {
                setData(json);
                setLoading(false);
            });
    }, [productId]);

    if (loading) return <div className="p-8 text-gray-500 animate-pulse">Running Bayesian Inference...</div>;

    return (
        <div className="p-6 bg-white rounded-xl shadow-lg border border-slate-200">
            <header className="mb-8">
                <h2 className="text-2xl font-bold text-slate-800">Bayesian Pricing Discovery</h2>
                <p className="text-slate-500">Product Analysis: <span className="font-mono text-indigo-600">{productId}</span></p>
            </header>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                    <span className="text-sm text-indigo-600 font-semibold uppercase">Optimal Price</span>
                    <div className="text-3xl font-bold text-indigo-900">${data?.optimal_price}</div>
                </div>
                {/* Visualizing the "Certainty" of the model */}
                <div className="p-4 bg-emerald-50 rounded-lg border border-emerald-100">
                    <span className="text-sm text-emerald-600 font-semibold uppercase">Winning Expected Revenue</span>
                    <div className="text-3xl font-bold text-emerald-900">
                        ${data?.all_experiments.find(e => e.price === data.optimal_price)?.expected_revenue}
                    </div>
                </div>
            </div>

            <table className="w-full text-left">
                <thead>
                    <tr className="text-slate-400 text-sm border-b">
                        <th className="pb-3 font-medium">Price Point</th>
                        <th className="pb-3 font-medium">Exp. Conv. Rate</th>
                        <th className="pb-3 font-medium">Exp. Revenue/View</th>
                        <th className="pb-3 font-medium">Confidence</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {data?.all_experiments.map((exp) => (
                        <tr key={exp.price} className={exp.price === data.optimal_price ? "bg-indigo-50/50" : ""}>
                            <td className="py-4 font-bold text-slate-700">${exp.price}</td>
                            <td className="py-4 text-slate-600">{exp.expected_conversion_rate}%</td>
                            <td className="py-4 font-semibold text-slate-900">${exp.expected_revenue}</td>
                            <td className="py-4">
                                <div className="w-24 h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div 
                                        className="h-full bg-indigo-500" 
                                        style={{ width: `${100 - exp.uncertainty_index}%` }}
                                    ></div>
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}