import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Edit, ArrowLeft } from 'lucide-react';
import roles from '@/routes/roles';

interface Role {
  id: string;
  name: string;
  status: string;
  description?: string;
  created_at: string;
  updated_at?: string;
}

interface Props {
  role: Role;
}

interface PageProps {
  currentCompany?: { id: string; name: string } | null;
  [key: string]: unknown;
}

export default function RoleShow({ role }: Props) {
  const { currentCompany } = usePage<PageProps>().props;
  const companyId = currentCompany!.id;

  const getStatusBadge = (status: string) => {
    return status === 'active' ? (
      <Badge variant="default" className="bg-green-100 text-green-800">
        Activo
      </Badge>
    ) : (
      <Badge variant="secondary" className="bg-red-100 text-red-800">
        Inactivo
      </Badge>
    );
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  return (
    <AppLayout>
      <Head title={`Rol: ${role.name}`} />

      <div className="py-12">
        <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
          {/* Header */}
          <div className="flex justify-between items-start mb-6">
            <div>
              <Link href={roles.index(companyId).url} className="text-blue-600 hover:text-blue-800 mb-2 inline-flex items-center">
                <ArrowLeft className="w-4 h-4 mr-1" />
                Volver a Roles
              </Link>
              <h1 className="text-3xl font-bold">{role.name}</h1>
              <div className="mt-2">
                {getStatusBadge(role.status)}
              </div>
            </div>

            <div className="flex gap-2">
              <Link href={roles.edit({ company: companyId, id: role.id }).url}>
                <Button>
                  <Edit className="w-4 h-4 mr-2" />
                  Editar
                </Button>
              </Link>
            </div>
          </div>

          {/* Role Details */}
          <div className="grid gap-6">
            <Card>
              <CardHeader>
                <CardTitle>Información del Rol</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <label className="text-sm font-medium text-gray-500">ID</label>
                  <p className="text-sm font-mono bg-gray-100 p-2 rounded">{role.id}</p>
                </div>

                <div>
                  <label className="text-sm font-medium text-gray-500">Nombre</label>
                  <p className="text-lg font-semibold">{role.name}</p>
                </div>

                <div>
                  <label className="text-sm font-medium text-gray-500">Estado</label>
                  <div className="mt-1">
                    {getStatusBadge(role.status)}
                  </div>
                </div>

                {role.description && (
                  <div>
                    <label className="text-sm font-medium text-gray-500">Descripción</label>
                    <p className="text-gray-700 whitespace-pre-wrap">{role.description}</p>
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Información de Auditoría</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <label className="text-sm font-medium text-gray-500">Fecha de Creación</label>
                  <p className="text-gray-700">{formatDate(role.created_at)}</p>
                </div>

                {role.updated_at && (
                  <div>
                    <label className="text-sm font-medium text-gray-500">Última Actualización</label>
                    <p className="text-gray-700">{formatDate(role.updated_at)}</p>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
