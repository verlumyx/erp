import React from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Edit, Mail, MailCheck, Calendar, Clock, Building, UserCircle } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import users from '@/routes/users';

interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    role?: {
        id: string;
        name: string;
    } | null;
    company?: {
        id: string;
        name: string;
    } | null;
}

interface Props {
    user: User;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function UserShow({ user }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const handleBack = () => {
        router.visit(users.index(companyId).url);
    };

    const handleEdit = () => {
        router.visit(users.edit({ company: companyId, id: user.id }).url);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AppLayout>
            <Head title={user.name} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex justify-between items-center mb-6">
                        <div className="flex items-center gap-4">
                            <button
                                onClick={handleBack}
                                className="text-gray-600 hover:text-gray-900 transition-colors"
                            >
                                <ArrowLeft className="w-5 h-5" />
                            </button>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">
                                    Detalles del Usuario
                                </h1>
                                <p className="text-gray-600">
                                    Información completa del usuario
                                </p>
                            </div>
                        </div>
                        <button
                            onClick={handleEdit}
                            className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                        >
                            <Edit className="w-4 h-4" />
                            Editar Usuario
                        </button>
                    </div>

                    {/* User Details Card */}
                    <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        {/* User Header */}
                        <div className="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-8">
                            <div className="flex items-center">
                                <div className="flex-shrink-0 h-20 w-20">
                                    <div className="h-20 w-20 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                                        <span className="text-2xl font-bold text-white">
                                            {user.name.charAt(0).toUpperCase()}
                                        </span>
                                    </div>
                                </div>
                                <div className="ml-6">
                                    <h2 className="text-2xl font-bold text-white">
                                        {user.name}
                                    </h2>
                                    <p className="text-blue-100">
                                        {user.email}
                                    </p>
                                    <div className="mt-2 flex items-center">
                                        {user.email_verified_at ? (
                                            <>
                                                <MailCheck className="w-4 h-4 text-green-300 mr-2" />
                                                <span className="text-green-100 text-sm">
                                                    Email verificado
                                                </span>
                                            </>
                                        ) : (
                                            <>
                                                <Mail className="w-4 h-4 text-yellow-300 mr-2" />
                                                <span className="text-yellow-100 text-sm">
                                                    Email pendiente de verificación
                                                </span>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* User Information */}
                        <div className="px-6 py-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Basic Information */}
                                <div className="space-y-4">
                                    <h3 className="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                        Información Básica
                                    </h3>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-500">
                                            Nombre Completo
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900">
                                            {user.name}
                                        </p>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-500">
                                            Correo Electrónico
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900">
                                            {user.email}
                                        </p>
                                    </div>

                                    {user.company && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-500">
                                                <Building className="w-4 h-4 inline mr-1" />
                                                Empresa
                                            </label>
                                            <p className="mt-1 text-sm text-gray-900">
                                                {user.company.name}
                                            </p>
                                        </div>
                                    )}

                                    {user.role && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-500">
                                                <UserCircle className="w-4 h-4 inline mr-1" />
                                                Rol
                                            </label>
                                            <p className="mt-1 text-sm text-gray-900">
                                                {user.role.name}
                                            </p>
                                        </div>
                                    )}
                                </div>

                                {/* Status and Dates */}
                                <div className="space-y-4">
                                    <h3 className="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                        Estado y Fechas
                                    </h3>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-500">
                                            Estado del Email
                                        </label>
                                        <div className="mt-1 flex items-center">
                                            {user.email_verified_at ? (
                                                <>
                                                    <MailCheck className="w-4 h-4 text-green-500 mr-2" />
                                                    <span className="text-sm text-green-700">
                                                        Verificado el {formatDate(user.email_verified_at)}
                                                    </span>
                                                </>
                                            ) : (
                                                <>
                                                    <Mail className="w-4 h-4 text-yellow-500 mr-2" />
                                                    <span className="text-sm text-yellow-700">
                                                        Pendiente de verificación
                                                    </span>
                                                </>
                                            )}
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-500">
                                            <Calendar className="w-4 h-4 inline mr-1" />
                                            Fecha de Registro
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900">
                                            {formatDate(user.created_at)}
                                        </p>
                                    </div>

                                    {user.updated_at && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-500">
                                                <Clock className="w-4 h-4 inline mr-1" />
                                                Última Actualización
                                            </label>
                                            <p className="mt-1 text-sm text-gray-900">
                                                {formatDate(user.updated_at)}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
